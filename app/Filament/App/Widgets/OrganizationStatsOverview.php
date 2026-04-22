<?php

namespace App\Filament\App\Widgets;

use App\Filament\App\Widgets\Concerns\InteractsWithOrganizationDashboard;
use App\Models\Comment;
use App\Models\Sprint;
use App\Support\Analytics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrganizationStatsOverview extends StatsOverviewWidget
{
    use InteractsWithOrganizationDashboard;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $organization = $this->getDashboardOrganization();
        $projectIds = $this->getFilteredProjectIds();
        $windowDays = $this->getDashboardWindowDays();
        $tasksQuery = $this->getFilteredTasksQuery();
        $taskTrend = Analytics::dailyCounts(clone $tasksQuery, $windowDays);
        $doneTrend = Analytics::dailyCounts((clone $tasksQuery)->where('status', 'done'), $windowDays, 'updated_at');
        $memberCount = $organization ? $organization->users()->count() : 0;

        return [
            Stat::make('Members', $memberCount)
                ->description('organization-wide access')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Projects', count($projectIds))
                ->description($this->getSelectedProjectId() ? 'filtered to one project' : 'current organization scope')
                ->descriptionIcon('heroicon-m-folder')
                ->color('primary'),
            Stat::make('Open tasks', (clone $tasksQuery)->where('status', '!=', 'done')->count())
                ->description("active work · {$windowDays}d window")
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->chart($taskTrend)
                ->color('primary'),
            Stat::make('Done in window', (clone $tasksQuery)
                ->where('status', 'done')
                ->where('updated_at', '>=', now()->subDays($windowDays))
                ->count())
                ->description("closed in {$windowDays}d")
                ->descriptionIcon('heroicon-m-check-circle')
                ->chart($doneTrend)
                ->color('primary'),
            Stat::make('Comments', Comment::query()
                ->where('created_at', '>=', now()->subDays($windowDays))
                ->whereHas('task', fn ($query) => $query->whereIn('project_id', $projectIds))
                ->count())
                ->description("team chatter · {$windowDays}d")
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('primary'),
            Stat::make('Active sprints', Sprint::query()
                ->whereIn('project_id', $projectIds)
                ->whereNotNull('started_at')
                ->whereNull('ended_at')
                ->count())
                ->description(($organization?->storyPointsPerSprint() ?? 20).' pts target / sprint')
                ->descriptionIcon('heroicon-m-rocket-launch')
                ->color('primary'),
        ];
    }
}
