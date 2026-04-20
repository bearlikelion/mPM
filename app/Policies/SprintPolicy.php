<?php

namespace App\Policies;

use App\Models\Sprint;
use App\Models\User;

class SprintPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('site_admin') ? true : null;
    }

    public function view(User $user, Sprint $sprint): bool
    {
        return app(ProjectPolicy::class)->view($user, $sprint->project);
    }

    public function update(User $user, Sprint $sprint): bool
    {
        return app(ProjectPolicy::class)->update($user, $sprint->project);
    }

    public function delete(User $user, Sprint $sprint): bool
    {
        return app(ProjectPolicy::class)->update($user, $sprint->project);
    }
}
