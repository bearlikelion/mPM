<?php

namespace App\Filament\App\Widgets\Concerns;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

trait InteractsWithOrganizationDashboard
{
    use InteractsWithPageFilters;

    protected function getDashboardOrganization(): ?Organization
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Organization ? $tenant : null;
    }

    protected function getDashboardWindowDays(): int
    {
        return in_array((int) ($this->pageFilters['window_days'] ?? 30), [7, 30, 90], true)
            ? (int) ($this->pageFilters['window_days'] ?? 30)
            : 30;
    }

    protected function getSelectedProjectId(): ?int
    {
        $organization = $this->getDashboardOrganization();
        $projectId = (int) ($this->pageFilters['project_id'] ?? 0);

        if (! $organization || $projectId < 1) {
            return null;
        }

        return Project::query()
            ->where('organization_id', $organization->id)
            ->whereKey($projectId)
            ->value('id');
    }

    protected function getFilteredProjectsQuery(): Builder
    {
        $organization = $this->getDashboardOrganization();
        $projectId = $this->getSelectedProjectId();

        return Project::query()
            ->when($organization, fn (Builder $query) => $query->where('organization_id', $organization->id), fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($projectId, fn (Builder $query) => $query->whereKey($projectId));
    }

    protected function getFilteredProjectIds(): array
    {
        return $this->getFilteredProjectsQuery()->pluck('id')->all();
    }

    protected function getFilteredTasksQuery(): Builder
    {
        $projectIds = $this->getFilteredProjectIds();

        return Task::query()->whereIn('project_id', $projectIds);
    }
}
