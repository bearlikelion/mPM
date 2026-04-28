<?php

use App\Livewire\ProjectWhiteboard;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Models\Whiteboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeMemberAndProject(): array
{
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['default_organization_id' => $organization->id]);
    $organization->users()->attach($user, ['role' => 'member', 'joined_at' => now()]);

    $project = Project::factory()->create(['organization_id' => $organization->id]);

    return [$user, $project];
}

test('project whiteboard route requires authentication', function (): void {
    [, $project] = makeMemberAndProject();

    $this->get(route('projects.whiteboard', $project))->assertRedirect(route('login'));
});

test('whiteboard redirect picks the first accessible project', function (): void {
    [$user, $project] = makeMemberAndProject();
    $this->actingAs($user);

    $this->get(route('whiteboard'))->assertRedirect(route('projects.whiteboard', $project));
});

test('whiteboard is auto-created on first visit and visible to org members', function (): void {
    [$user, $project] = makeMemberAndProject();
    $this->actingAs($user);

    Livewire::test(ProjectWhiteboard::class, ['project' => $project])
        ->assertOk();

    $this->assertDatabaseHas('whiteboards', ['project_id' => $project->id]);
});

test('non-org members cannot view a project whiteboard', function (): void {
    [, $project] = makeMemberAndProject();
    $outsider = User::factory()->create();
    $this->actingAs($outsider);

    $this->get(route('projects.whiteboard', $project))->assertNotFound();
});

test('save persists scene data', function (): void {
    [$user, $project] = makeMemberAndProject();
    $this->actingAs($user);

    $payload = [
        'elements' => [['type' => 'rectangle', 'id' => 'r1']],
        'appState' => ['viewBackgroundColor' => 'transparent'],
        'files' => [],
    ];

    Livewire::test(ProjectWhiteboard::class, ['project' => $project])
        ->call('save', $payload);

    $whiteboard = Whiteboard::where('project_id', $project->id)->first();
    expect($whiteboard->data)->toBe($payload)
        ->and($whiteboard->updated_by)->toBe($user->id);
});

test('commitImage stores upload to whiteboard media collection', function (): void {
    Storage::fake('public');

    [$user, $project] = makeMemberAndProject();
    $this->actingAs($user);

    $file = UploadedFile::fake()->image('sketch.png', 200, 200);

    Livewire::test(ProjectWhiteboard::class, ['project' => $project])
        ->set('pendingImage', $file)
        ->call('commitImage')
        ->assertHasNoErrors();

    expect($project->fresh()->getMedia('whiteboard'))->toHaveCount(1);
});
