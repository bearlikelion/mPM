<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SiteTenant
{
    public function organizations(User $user): Collection
    {
        return $user->organizations()
            ->orderBy('name')
            ->get();
    }

    public function currentOrganization(User $user): ?Organization
    {
        $organizations = $this->organizations($user);

        if ($organizations->isEmpty()) {
            return null;
        }

        $defaultOrganization = $user->defaultOrganization;

        if ($defaultOrganization && $organizations->contains('id', $defaultOrganization->id)) {
            return $organizations->firstWhere('id', $defaultOrganization->id);
        }

        return $organizations->first();
    }

    public function projectsQuery(User $user, ?Organization $organization = null): Builder
    {
        $query = Project::query()->orderBy('name');

        if (! $organization) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereBelongsTo($organization);
    }

    public function validProjectId(User $user, ?int $projectId, ?Organization $organization = null): ?int
    {
        if (! $projectId || ! $organization) {
            return null;
        }

        return $this->projectsQuery($user, $organization)
            ->whereKey($projectId)
            ->value('id');
    }

    public function switchOrganization(User $user, Organization $organization): void
    {
        $user->update([
            'default_organization_id' => $organization->id,
        ]);

        $user->unsetRelation('defaultOrganization');
    }
}
