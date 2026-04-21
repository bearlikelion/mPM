<?php

namespace Tests\Feature;

use App\Livewire\CreateTaskModal;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateTaskModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_modal_can_be_opened_via_event_listener(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'default_organization_id' => $organization->id,
        ]);

        $organization->users()->attach($user, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        Project::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($user);

        Livewire::test(CreateTaskModal::class)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    public function test_users_can_create_and_assign_a_task_from_the_global_modal(): void
    {
        $organization = Organization::factory()->create();
        $creator = User::factory()->create([
            'default_organization_id' => $organization->id,
        ]);
        $assignee = User::factory()->create();

        $organization->users()->attach($creator, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
        $organization->users()->attach($assignee, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $project = Project::factory()->create([
            'organization_id' => $organization->id,
            'key' => 'OPS',
            'task_counter' => 0,
        ]);

        $this->actingAs($creator);

        Livewire::test(CreateTaskModal::class)
            ->set('projectId', $project->id)
            ->set('title', 'Ship global task modal')
            ->set('description', 'Create and assign work from any page.')
            ->set('priority', 'high')
            ->set('status', 'todo')
            ->set('storyPoints', 5)
            ->call('toggleAssignee', $assignee->id)
            ->call('createTask')
            ->assertHasNoErrors()
            ->assertRedirect(route('tasks.show', 'OPS-1'));

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'key' => 'OPS-1',
            'title' => 'Ship global task modal',
            'priority' => 'high',
            'status' => 'todo',
            'story_points' => 5,
        ]);

        $taskId = Task::query()->where('key', 'OPS-1')->value('id');

        $this->assertDatabaseHas('task_user', [
            'task_id' => $taskId,
            'user_id' => $assignee->id,
        ]);
    }

    public function test_modal_only_offers_projects_from_the_active_organization(): void
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

        Project::factory()->create([
            'organization_id' => $organizationA->id,
            'name' => 'Alpha Intake',
        ]);
        Project::factory()->create([
            'organization_id' => $organizationB->id,
            'name' => 'Beta Intake',
        ]);

        $this->actingAs($user);

        Livewire::test(CreateTaskModal::class)
            ->assertSee('Alpha Intake')
            ->assertDontSee('Beta Intake');
    }
}
