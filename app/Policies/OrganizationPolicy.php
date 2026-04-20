<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('site_admin') ? true : null;
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->organizations()->whereKey($organization->id)->exists();
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->organizations()
            ->whereKey($organization->id)
            ->wherePivot('role', 'org_admin')
            ->exists();
    }

    public function delete(User $user, Organization $organization): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return true;
    }
}
