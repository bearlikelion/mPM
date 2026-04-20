<?php

namespace App\Policies;

use App\Models\Epic;
use App\Models\User;

class EpicPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('site_admin') ? true : null;
    }

    public function view(User $user, Epic $epic): bool
    {
        return app(ProjectPolicy::class)->view($user, $epic->project);
    }

    public function update(User $user, Epic $epic): bool
    {
        return app(ProjectPolicy::class)->update($user, $epic->project);
    }

    public function delete(User $user, Epic $epic): bool
    {
        return app(ProjectPolicy::class)->update($user, $epic->project);
    }
}
