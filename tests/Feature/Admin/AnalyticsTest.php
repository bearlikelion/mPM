<?php

use App\Filament\Admin\Resources\OrganizationResource\Pages\ViewOrganization;
use App\Filament\Admin\Widgets\StatsOverview;
use App\Filament\Admin\Widgets\TaskBreakdownWidget;
use App\Filament\Admin\Widgets\TopOrgsWidget;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Analytics;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
    filament()->setCurrentPanel('admin');
});

it('formats bytes as human readable', function () {
    expect(Analytics::humanBytes(0))->toBe('0 B')
        ->and(Analytics::humanBytes(1024))->toBe('1 KB')
        ->and(Analytics::humanBytes(1536))->toBe('1.5 KB')
        ->and(Analytics::humanBytes(1048576))->toBe('1 MB');
});

it('computes daily counts for last N days', function () {
    User::factory()->create(['created_at' => now()->subDays(2)]);
    User::factory()->create(['created_at' => now()->subDays(2)]);
    User::factory()->create(['created_at' => now()]);

    $counts = Analytics::dailyCounts(User::query(), 7);

    expect($counts)->toHaveCount(7)
        ->and(array_sum($counts))->toBeGreaterThanOrEqual(3);
});

it('computes task status counts', function () {
    $project = Project::factory()->create();
    Task::factory()->create(['project_id' => $project->id, 'status' => 'todo']);
    Task::factory()->create(['project_id' => $project->id, 'status' => 'done']);
    Task::factory()->create(['project_id' => $project->id, 'status' => 'done']);

    $counts = Analytics::taskStatusCounts();

    expect($counts['todo'])->toBe(1)
        ->and($counts['done'])->toBe(2)
        ->and($counts['in_progress'])->toBe(0);
});

it('renders admin dashboard widgets', function () {
    $admin = User::factory()->create();
    $admin->assignRole('site_admin');
    Organization::factory()->count(2)->create();
    $this->actingAs($admin);

    Livewire::test(StatsOverview::class)->assertSeeText('Users');
    Livewire::test(TaskBreakdownWidget::class)->assertSeeText('By status');
    Livewire::test(TopOrgsWidget::class)->assertSeeText('Top organizations');
});

it('renders organization view page with stats', function () {
    $admin = User::factory()->create();
    $admin->assignRole('site_admin');
    $org = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $org->id]);
    Task::factory()->count(3)->create(['project_id' => $project->id, 'status' => 'todo']);
    Task::factory()->create(['project_id' => $project->id, 'status' => 'done']);

    $this->actingAs($admin);

    Livewire::test(ViewOrganization::class, ['record' => $org->id])
        ->assertSeeText('Task status distribution')
        ->assertSeeText('Projects')
        ->assertSeeText('Members');
});
