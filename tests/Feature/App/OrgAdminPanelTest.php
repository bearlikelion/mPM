<?php

use App\Filament\App\Pages\RegisterOrganization;
use App\Filament\App\Pages\SprintSettings;
use App\Filament\App\Resources\OrganizationMemberResource\Pages\EditOrganizationMember;
use App\Filament\App\Widgets\OrganizationProjectLoadWidget;
use App\Filament\App\Widgets\OrganizationStatsOverview;
use App\Filament\App\Widgets\OrganizationTaskBreakdownWidget;
use App\Filament\App\Widgets\OrganizationTeamLoadWidget;
use App\Models\Comment;
use App\Models\Organization;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
    filament()->setCurrentPanel('app');
});

function bootTenantPanel(Organization $organization): void
{
    Filament::setTenant($organization);
    Filament::bootCurrentPanel();
}

it('registers a new organization from the app panel', function () {
    SiteSetting::current()->update([
        'org_creation_enabled' => true,
        'org_limit_per_user' => 3,
    ]);

    $existingOrganization = Organization::factory()->create();
    $user = User::factory()->create([
        'default_organization_id' => $existingOrganization->id,
    ]);

    $existingOrganization->users()->attach($user, [
        'role' => 'org_admin',
        'joined_at' => now(),
    ]);

    $this->actingAs($user);
    bootTenantPanel($existingOrganization);

    Livewire::test(RegisterOrganization::class)
        ->fillForm([
            'name' => 'Launch Ops',
            'slug' => 'launch-ops',
            'timezone' => 'America/New_York',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $newOrganization = Organization::where('slug', 'launch-ops')->first();

    expect($newOrganization)->not->toBeNull()
        ->and($newOrganization?->users()->whereKey($user->id)->exists())->toBeTrue()
        ->and($newOrganization?->users()->whereKey($user->id)->wherePivot('role', 'org_admin')->exists())->toBeTrue();
});

it('saves sprint settings for the current organization', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'default_organization_id' => $organization->id,
    ]);

    $organization->users()->attach($admin, [
        'role' => 'org_admin',
        'joined_at' => now(),
    ]);

    $this->actingAs($admin);
    bootTenantPanel($organization);

    Livewire::test(SprintSettings::class)
        ->fillForm([
            'sprint_length_days' => 10,
            'story_points_per_sprint' => 34,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($organization->fresh()->sprintSettings())->toBe([
        'sprint_length_days' => 10,
        'story_points_per_sprint' => 34,
    ]);
});

it('updates member roles and project assignments for the current organization', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'default_organization_id' => $organization->id,
    ]);
    $member = User::factory()->create();
    $projectA = Project::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Alpha',
    ]);
    $projectB = Project::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Beta',
    ]);

    $organization->users()->attach($admin, [
        'role' => 'org_admin',
        'joined_at' => now(),
    ]);
    $organization->users()->attach($member, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $this->actingAs($admin);
    bootTenantPanel($organization);

    Livewire::test(EditOrganizationMember::class, ['record' => $member->id])
        ->fillForm([
            'organization_role' => 'project_admin',
            'project_memberships' => [
                [
                    'project_id' => $projectA->id,
                    'role' => 'project_admin',
                ],
                [
                    'project_id' => $projectB->id,
                    'role' => 'member',
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($member->fresh()->organizationRoleFor($organization->id))->toBe('project_admin')
        ->and($member->projects()->whereKey($projectA->id)->wherePivot('role', 'project_admin')->exists())->toBeTrue()
        ->and($member->projects()->whereKey($projectB->id)->wherePivot('role', 'member')->exists())->toBeTrue();
});

it('renders org dashboard widgets with tenant scoped metrics', function () {
    $organization = Organization::factory()->create([
        'settings' => [
            'sprint_length_days' => 14,
            'story_points_per_sprint' => 20,
        ],
    ]);
    $admin = User::factory()->create([
        'default_organization_id' => $organization->id,
    ]);
    $member = User::factory()->create([
        'name' => 'Metrics Member',
        'email' => 'metrics@example.com',
    ]);
    $project = Project::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Core Platform',
        'key' => 'CORE',
    ]);
    $sprint = Sprint::factory()->active()->create([
        'project_id' => $project->id,
        'name' => 'Sprint 7',
    ]);
    $openTask = Task::factory()->create([
        'project_id' => $project->id,
        'sprint_id' => $sprint->id,
        'status' => 'in_progress',
        'story_points' => 8,
        'title' => 'Instrument dashboards',
    ]);
    $doneTask = Task::factory()->create([
        'project_id' => $project->id,
        'sprint_id' => $sprint->id,
        'status' => 'done',
        'story_points' => 5,
        'updated_at' => now()->subDays(2),
        'title' => 'Ship filters',
    ]);

    $organization->users()->attach($admin, [
        'role' => 'org_admin',
        'joined_at' => now(),
    ]);
    $organization->users()->attach($member, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $openTask->assignees()->attach($member);
    $doneTask->assignees()->attach($member);

    Comment::factory()->create([
        'task_id' => $openTask->id,
        'user_id' => $member->id,
        'created_at' => now()->subDay(),
    ]);

    $this->actingAs($admin);
    bootTenantPanel($organization);

    Livewire::test(OrganizationStatsOverview::class)
        ->assertSeeText('Members')
        ->assertSeeText('Active sprints');

    Livewire::test(OrganizationTaskBreakdownWidget::class)
        ->assertSeeText('Task distribution')
        ->assertSeeText('Delivery health')
        ->assertSeeText('By status');

    Livewire::test(OrganizationProjectLoadWidget::class)
        ->assertSeeText('Core Platform')
        ->assertSeeText('Sprint 7');

    Livewire::test(OrganizationTeamLoadWidget::class)
        ->assertSeeText('Metrics Member')
        ->assertSeeText('metrics@example.com');
});
