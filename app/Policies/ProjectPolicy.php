<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('site_admin') ? true : null;
    }

    public function view(User $user, Project $project): bool
    {
        if ($project->visibility === Project::VISIBILITY_PUBLIC) {
            return true;
        }

        if (! $this->isOrgMember($user, $project)) {
            return false;
        }

        if ($project->visibility === Project::VISIBILITY_ORG) {
            return true;
        }

        return $project->members()->whereKey($user->id)->exists()
            || $this->isOrgAdmin($user, $project);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->isOrgAdmin($user, $project)
            || $project->members()
                ->whereKey($user->id)
                ->wherePivot('role', 'project_admin')
                ->exists();
    }

    public function create(User $user): bool
    {
        return $user->organizations()->wherePivot('role', 'org_admin')->exists();
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->isOrgAdmin($user, $project);
    }

    private function isOrgMember(User $user, Project $project): bool
    {
        return $user->organizations()->whereKey($project->organization_id)->exists();
    }

    private function isOrgAdmin(User $user, Project $project): bool
    {
        return $user->organizations()
            ->whereKey($project->organization_id)
            ->wherePivot('role', 'org_admin')
            ->exists();
    }
}
