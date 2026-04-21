<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KanbanTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_query_parameter_opens_the_task_drawer(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'default_organization_id' => $organization->id,
        ]);

        $organization->users()->attach($user, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $project = Project::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'key' => 'MPM-204',
            'title' => 'Open kanban drawer',
        ]);

        $this->actingAs($user)
            ->get(route('kanban', ['project' => $project->id, 'task' => $task->key]))
            ->assertOk()
            ->assertSee('open full view')
            ->assertSee(route('tasks.show', $task->key), false)
            ->assertSee($task->title);
    }

    public function test_planning_pages_render_after_searchable_filter_migration(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'default_organization_id' => $organization->id,
        ]);

        $organization->users()->attach($user, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $project = Project::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($user)
            ->get(route('backlog', ['project' => $project->id]))
            ->assertOk()
            ->assertSee('tasks waiting for a sprint');

        $this->actingAs($user)
            ->get(route('epics', ['project' => $project->id]))
            ->assertOk()
            ->assertSee('Epics');

        $this->actingAs($user)
            ->get(route('sprints', ['project' => $project->id]))
            ->assertOk()
            ->assertSee('sprint schedule');
    }

    public function test_planning_pages_only_show_projects_from_the_active_organization(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
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
            'name' => 'Alpha Roadmap',
        ]);
        $projectB = Project::factory()->create([
            'organization_id' => $organizationB->id,
            'name' => 'Beta Roadmap',
        ]);

        Task::factory()->create([
            'project_id' => $projectA->id,
            'title' => 'Alpha planning task',
        ]);
        Task::factory()->create([
            'project_id' => $projectB->id,
            'title' => 'Beta planning task',
        ]);

        $this->actingAs($user)
            ->get(route('kanban', ['project' => $projectB->id]))
            ->assertOk()
            ->assertSee('Alpha Roadmap')
            ->assertDontSee('Beta Roadmap')
            ->assertSee('Alpha planning task')
            ->assertDontSee('Beta planning task');

        $this->actingAs($user)
            ->get(route('epics'))
            ->assertOk()
            ->assertSee('Alpha Roadmap')
            ->assertDontSee('Beta Roadmap');

        $this->actingAs($user)
            ->get(route('backlog'))
            ->assertOk()
            ->assertSee('Alpha Roadmap')
            ->assertDontSee('Beta Roadmap');

        $this->actingAs($user)
            ->get(route('sprints'))
            ->assertOk()
            ->assertSee('Alpha Roadmap')
            ->assertDontSee('Beta Roadmap');
    }
}
