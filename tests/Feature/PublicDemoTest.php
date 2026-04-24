<?php

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\PublicDemoWorkspace;

it('resets the public demo workspace from the scaffold dataset', function () {
    $this->artisan('demo:reset')
        ->assertSuccessful();

    $organization = Organization::query()->where('slug', 'public-demo')->firstOrFail();
    $demoUser = User::query()->where('email', 'demo@example.test')->firstOrFail();

    expect($organization->projects()->count())->toBe(2)
        ->and($organization->tasks()->count())->toBe(9)
        ->and($demoUser->default_organization_id)->toBe($organization->id)
        ->and($demoUser->organizations()->whereKey($organization->id)->wherePivot('role', 'org_admin')->exists())->toBeTrue()
        ->and(Task::query()->where('key', 'DEMO-3')->firstOrFail()->blockers()->where('key', 'DEMO-2')->exists())->toBeTrue();
});

it('removes visitor changes on the next public demo reset', function () {
    app(PublicDemoWorkspace::class)->reset();

    $organization = Organization::query()->where('slug', 'public-demo')->firstOrFail();
    $project = Project::query()->where('organization_id', $organization->id)->where('key', 'DEMO')->firstOrFail();

    Task::factory()->create([
        'project_id' => $project->id,
        'key' => 'DEMO-999',
        'title' => 'Visitor-created temporary task',
    ]);

    expect(Task::query()->where('key', 'DEMO-999')->exists())->toBeTrue();

    app(PublicDemoWorkspace::class)->reset();

    expect(Task::query()->where('key', 'DEMO-999')->exists())->toBeFalse()
        ->and(Organization::query()->where('slug', 'public-demo')->firstOrFail()->tasks()->count())->toBe(9);
});

it('logs guests into the public demo user and opens the dashboard', function () {
    $response = $this->get(route('demo'));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs(User::query()->where('email', 'demo@example.test')->firstOrFail());
});
