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
            ->assertSee('Open full view')
            ->assertSee(route('tasks.show', $task->key), false)
            ->assertSee($task->title);
    }
}
