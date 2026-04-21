<?php

namespace Tests\Feature;

use App\Livewire\TaskDetail;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskBlockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_prioritizes_blocking_tasks_and_shows_dependency_notices(): void
    {
        [$user, $project] = $this->memberWithProject();

        $blockingTask = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Blocking work',
            'status' => 'todo',
            'priority' => 'med',
        ]);
        $neutralTask = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Neutral work',
            'status' => 'todo',
            'priority' => 'med',
        ]);
        $blockedTask = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Blocked work',
            'status' => 'todo',
            'priority' => 'med',
        ]);

        $blockedTask->blockers()->attach($blockingTask);

        $blockingTask->assignees()->attach($user);
        $neutralTask->assignees()->attach($user);
        $blockedTask->assignees()->attach($user);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder([
                'Blocking work',
                'Neutral work',
                'Blocked work',
            ])
            ->assertSee('This task is blocking Blocked work')
            ->assertSee('Blocked by')
            ->assertSee(route('tasks.show', $blockingTask->key), false);
    }

    public function test_kanban_sorts_blocking_tasks_above_blocked_tasks_and_renders_links(): void
    {
        [$user, $project] = $this->memberWithProject();

        $blockingTask = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Blocking work',
            'status' => 'todo',
            'priority' => 'med',
        ]);
        $neutralTask = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Neutral work',
            'status' => 'todo',
            'priority' => 'med',
        ]);
        $blockedTask = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Blocked work',
            'status' => 'todo',
            'priority' => 'med',
        ]);

        $blockedTask->blockers()->attach($blockingTask);

        $response = $this->actingAs($user)->get(route('kanban', ['project' => $project->id]));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'Blocking work',
                'Neutral work',
                'Blocked work',
            ])
            ->assertSee('This task is blocking Blocked work')
            ->assertSee('Blocked by')
            ->assertSee(route('tasks.show', $blockingTask->key), false);
    }

    public function test_task_detail_can_sync_blockers_for_a_task(): void
    {
        [$user, $project] = $this->memberWithProject();

        $blockingTask = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Blocking work',
        ]);
        $blockedTask = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Blocked work',
        ]);

        $this->actingAs($user);

        Livewire::test(TaskDetail::class, ['taskKey' => $blockedTask->key])
            ->set('blockerIds', [$blockingTask->id])
            ->assertSee('Blocked by')
            ->assertSee('Blocking work');

        $this->assertDatabaseHas('task_blockers', [
            'task_id' => $blockedTask->id,
            'blocking_task_id' => $blockingTask->id,
        ]);
    }

    protected function memberWithProject(): array
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

        return [$user, $project];
    }
}
