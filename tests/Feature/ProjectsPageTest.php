<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_page_is_visible_to_authenticated_users(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Nerdibear',
        ]);
        $user = User::factory()->create([
            'default_organization_id' => $organization->id,
        ]);

        $organization->users()->attach($user, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $project = Project::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Control Center',
            'key' => 'CTRL',
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($user)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee('Control Center')
            ->assertSee(route('kanban', ['project' => $project->id]), false);
    }

    public function test_projects_page_only_lists_projects_from_the_active_organization(): void
    {
        $organizationA = Organization::factory()->create([
            'name' => 'Alpha Org',
        ]);
        $organizationB = Organization::factory()->create([
            'name' => 'Beta Org',
        ]);
        $user = User::factory()->create([
            'default_organization_id' => $organizationA->id,
        ]);

        $organizationA->users()->attach($user, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
        $organizationB->users()->attach($user, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $projectA = Project::factory()->create([
            'organization_id' => $organizationA->id,
            'name' => 'Alpha Control',
        ]);
        $projectB = Project::factory()->create([
            'organization_id' => $organizationB->id,
            'name' => 'Beta Control',
        ]);

        $this->actingAs($user)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee('Alpha Control')
            ->assertDontSee('Beta Control')
            ->assertDontSee(route('kanban', ['project' => $projectB->id]), false);

        $this->actingAs($user)
            ->from(route('projects.index'))
            ->post(route('organizations.switch', $organizationB))
            ->assertRedirect(route('projects.index', absolute: false));

        $this->actingAs($user)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee('Beta Control')
            ->assertDontSee('Alpha Control')
            ->assertDontSee(route('kanban', ['project' => $projectA->id]), false);
    }
}
