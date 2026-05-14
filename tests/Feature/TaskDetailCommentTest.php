<?php

use App\Livewire\TaskDetail;
use App\Models\Epic;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('task detail can add a rich text comment body', function (): void {
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
    ]);

    $this->actingAs($user);

    Livewire::test(TaskDetail::class, ['taskKey' => $task->key])
        ->set('newComment', '<p><strong>Ready for review.</strong></p>')
        ->call('addComment')
        ->assertHasNoErrors()
        ->assertSet('newComment', '');

    $this->assertDatabaseHas('comments', [
        'task_id' => $task->id,
        'user_id' => $user->id,
        'body' => '<p><strong>Ready for review.</strong></p>',
    ]);
});

test('task detail can edit core task fields after creation', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'default_organization_id' => $organization->id,
    ]);
    $assignee = User::factory()->create();

    $organization->users()->attach($user, [
        'role' => 'member',
        'joined_at' => now(),
    ]);
    $organization->users()->attach($assignee, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $project = Project::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $epic = Epic::factory()->create([
        'project_id' => $project->id,
    ]);
    $sprint = Sprint::factory()->create([
        'project_id' => $project->id,
    ]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'title' => 'Original title',
        'description' => '<p>Original description.</p>',
    ]);

    $this->actingAs($user);

    Livewire::test(TaskDetail::class, ['taskKey' => $task->key])
        ->assertSet('editingDetails', false)
        ->call('editDetails')
        ->assertSet('editingDetails', true)
        ->set('title', 'Updated title')
        ->set('description', '<p><strong>Updated description.</strong></p>')
        ->set('dueDate', '2026-06-15')
        ->set('epicId', $epic->id)
        ->set('sprintId', $sprint->id)
        ->set('assigneeIds', [$assignee->id])
        ->call('saveDetails')
        ->assertHasNoErrors()
        ->assertSet('editingDetails', false)
        ->assertSet('title', 'Updated title')
        ->assertSet('epicId', $epic->id)
        ->assertSet('sprintId', $sprint->id)
        ->assertSet('assigneeIds', [$assignee->id]);

    $task->refresh();

    expect($task->title)->toBe('Updated title')
        ->and((string) $task->description)->toBe('<p><strong>Updated description.</strong></p>')
        ->and($task->due_date?->format('Y-m-d'))->toBe('2026-06-15')
        ->and($task->epic_id)->toBe($epic->id)
        ->and($task->sprint_id)->toBe($sprint->id)
        ->and($task->assignees()->whereKey($assignee->id)->exists())->toBeTrue();
});

test('task detail edit mode can be cancelled without saving changes', function (): void {
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
        'title' => 'Original title',
    ]);

    $this->actingAs($user);

    Livewire::test(TaskDetail::class, ['taskKey' => $task->key])
        ->call('editDetails')
        ->assertSet('editingDetails', true)
        ->set('title', 'Unsaved title')
        ->call('cancelEditingDetails')
        ->assertSet('editingDetails', false)
        ->assertSet('title', 'Original title');

    expect($task->fresh()->title)->toBe('Original title');
});
