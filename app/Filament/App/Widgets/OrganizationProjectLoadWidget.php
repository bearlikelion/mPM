<?php

namespace App\Filament\App\Widgets;

use App\Filament\App\Widgets\Concerns\InteractsWithOrganizationDashboard;
use App\Models\Project;
use Filament\Widgets\Widget;

class OrganizationProjectLoadWidget extends Widget
{
    use InteractsWithOrganizationDashboard;

    protected string $view = 'filament.app.widgets.organization-project-load';

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected static ?int $sort = 3;

    protected function getViewData(): array
    {
        $organization = $this->getDashboardOrganization();
        $windowDays = $this->getDashboardWindowDays();

        return [
            'projects' => $this->getFilteredProjectsQuery()
                ->withCount([
                    'members',
                    'tasks',
                    'tasks as open_tasks_count' => fn ($query) => $query->where('status', '!=', 'done'),
                    'tasks as completed_tasks_count' => fn ($query) => $query
                        ->where('status', 'done')
                        ->where('updated_at', '>=', now()->subDays($windowDays)),
                ])
                ->orderByDesc('open_tasks_count')
                ->orderBy('name')
                ->limit(6)
                ->get()
                ->map(function (Project $project) use ($organization): array {
                    $activeSprint = $project->sprints()
                        ->whereNotNull('started_at')
                        ->whereNull('ended_at')
                        ->latest('starts_at')
                        ->first();

                    return [
                        'name' => $project->name,
                        'key' => $project->key,
                        'members' => $project->members_count,
                        'tasks' => $project->tasks_count,
                        'open_tasks' => $project->open_tasks_count,
                        'completed_tasks' => $project->completed_tasks_count,
                        'active_sprint' => $activeSprint?->name,
                        'active_sprint_points' => $activeSprint ? (int) $activeSprint->tasks()->sum('story_points') : 0,
                        'story_points_target' => $organization?->storyPointsPerSprint() ?? 20,
                    ];
                })
                ->all(),
            'windowDays' => $windowDays,
        ];
    }
}
