<?php

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskActivityNotification;
use App\Support\TaskActivityNotifier;
use Illuminate\Support\Facades\Notification;

it('notifies mentioned organization users', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $actor = User::factory()->create(['name' => 'Actor Person']);
    $mentioned = User::factory()->create(['name' => 'Review Human', 'email' => 'review@example.com']);
    $task = Task::factory()->create(['project_id' => $project->id, 'key' => 'APP-1']);

    $organization->users()->attach($actor, ['role' => 'member', 'joined_at' => now()]);
    $organization->users()->attach($mentioned, ['role' => 'member', 'joined_at' => now()]);

    app(TaskActivityNotifier::class)->mentioned($task, 'Can @review check this?', $actor);

    Notification::assertSentTo($mentioned, TaskActivityNotification::class, function (TaskActivityNotification $notification): bool {
        return $notification->kind === 'mentioned';
    });
});

it('notifies assignees when blockers are added and cleared', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $actor = User::factory()->create();
    $assignee = User::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->id, 'key' => 'APP-1']);
    $blocker = Task::factory()->create(['project_id' => $project->id, 'key' => 'APP-2']);

    $organization->users()->attach($actor, ['role' => 'member', 'joined_at' => now()]);
    $organization->users()->attach($assignee, ['role' => 'member', 'joined_at' => now()]);
    $task->assignees()->attach($assignee);

    app(TaskActivityNotifier::class)->blockersChanged($task, collect([$blocker]), collect(), $actor);
    app(TaskActivityNotifier::class)->blockersChanged($task, collect(), collect([$blocker]), $actor);

    Notification::assertSentToTimes($assignee, TaskActivityNotification::class, 2);
});
