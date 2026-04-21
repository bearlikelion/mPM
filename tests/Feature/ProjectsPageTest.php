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
            ->assertSee('Projects overview')
            ->assertSee('Control Center')
            ->assertSee(route('kanban', ['project' => $project->id]), false);
    }
}
