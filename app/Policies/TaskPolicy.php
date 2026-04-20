<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('site_admin') ? true : null;
    }

    public function view(User $user, Task $task): bool
    {
        return app(ProjectPolicy::class)->view($user, $task->project);
    }

    public function update(User $user, Task $task): bool
    {
        return app(ProjectPolicy::class)->view($user, $task->project);
    }

    public function create(User $user, Task $task): bool
    {
        return app(ProjectPolicy::class)->view($user, $task->project);
    }

    public function delete(User $user, Task $task): bool
    {
        return app(ProjectPolicy::class)->update($user, $task->project)
            || $task->created_by === $user->id;
    }
}
