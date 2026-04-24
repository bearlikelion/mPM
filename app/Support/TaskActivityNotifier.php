<?php

namespace App\Support;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskActivityNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TaskActivityNotifier
{
    public function taskAssigned(Task $task, User $assignee, ?User $actor = null): void
    {
        if ($actor && (int) $actor->id === (int) $assignee->id) {
            return;
        }

        $assignee->notify(new TaskActivityNotification(
            kind: 'task_assigned',
            task: $task,
            title: 'New task assigned',
            body: "{$task->key} · {$task->title}",
            actor: $actor,
        ));
    }

    public function reviewRequested(Task $task, ?User $actor = null): void
    {
        $task->loadMissing('assignees');

        $task->assignees
            ->reject(fn (User $user): bool => $actor && (int) $actor->id === (int) $user->id)
            ->each(fn (User $user) => $user->notify(new TaskActivityNotification(
                kind: 'review_requested',
                task: $task,
                title: 'Task ready for review',
                body: "{$task->key} is assigned to review.",
                actor: $actor,
            )));
    }

    public function mentioned(Task $task, string $body, ?User $actor = null): void
    {
        $this->mentionedUsers($task, $body)
            ->reject(fn (User $user): bool => $actor && (int) $actor->id === (int) $user->id)
            ->each(fn (User $user) => $user->notify(new TaskActivityNotification(
                kind: 'mentioned',
                task: $task,
                title: 'You were mentioned',
                body: "{$task->key} · {$task->title}",
                actor: $actor,
            )));
    }

    /**
     * @param  Collection<int, Task>  $added
     * @param  Collection<int, Task>  $removed
     */
    public function blockersChanged(Task $task, Collection $added, Collection $removed, ?User $actor = null): void
    {
        $task->loadMissing('assignees');

        $task->assignees->each(function (User $user) use ($task, $added, $removed, $actor): void {
            if ($actor && (int) $actor->id === (int) $user->id) {
                return;
            }

            if ($added->isNotEmpty()) {
                $user->notify(new TaskActivityNotification(
                    kind: 'blocker_added',
                    task: $task,
                    title: 'Blocking task added',
                    body: $added->pluck('key')->join(', ')." now blocks {$task->key}.",
                    actor: $actor,
                ));
            }

            if ($removed->isNotEmpty()) {
                $user->notify(new TaskActivityNotification(
                    kind: 'blocker_cleared',
                    task: $task,
                    title: 'Blocking task cleared',
                    body: $removed->pluck('key')->join(', ')." no longer blocks {$task->key}.",
                    actor: $actor,
                ));
            }
        });
    }

    /**
     * @return Collection<int, User>
     */
    private function mentionedUsers(Task $task, string $body): Collection
    {
        $task->loadMissing('project.organization.users');
        $normalizedBody = Str::lower($body);

        return $task->project->organization->users
            ->filter(function (User $user) use ($normalizedBody): bool {
                $emailLocalPart = Str::lower(Str::before($user->email, '@'));
                $name = Str::lower($user->name);

                return Str::contains($normalizedBody, '@'.$emailLocalPart)
                    || Str::contains($normalizedBody, '@'.$name);
            })
            ->values();
    }
}
