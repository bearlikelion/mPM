<?php

use App\Livewire\TaskDetail;
use App\Models\Organization;
use App\Models\Project;
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
