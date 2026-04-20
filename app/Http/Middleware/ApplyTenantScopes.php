<?php

namespace App\Http\Middleware;

use App\Models\Epic;
use App\Models\OrganizationInvite;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Tag;
use App\Models\Task;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApplyTenantScopes
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = Filament::getTenant();

        if ($tenant) {
            Project::addGlobalScope('tenant', fn (Builder $q) => $q->where('organization_id', $tenant->id));
            Tag::addGlobalScope('tenant', fn (Builder $q) => $q->where('organization_id', $tenant->id));
            OrganizationInvite::addGlobalScope('tenant', fn (Builder $q) => $q->where('organization_id', $tenant->id));

            $projectIds = Project::query()->withoutGlobalScope('tenant')
                ->where('organization_id', $tenant->id)
                ->pluck('id');

            Epic::addGlobalScope('tenant', fn (Builder $q) => $q->whereIn('project_id', $projectIds));
            Sprint::addGlobalScope('tenant', fn (Builder $q) => $q->whereIn('project_id', $projectIds));
            Task::addGlobalScope('tenant', fn (Builder $q) => $q->whereIn('project_id', $projectIds));
        }

        return $next($request);
    }
}
