<?php

namespace App\Filament\App\Widgets;

use App\Filament\App\Widgets\Concerns\InteractsWithOrganizationDashboard;
use Filament\Widgets\Widget;

class OrganizationTaskBreakdownWidget extends Widget
{
    use InteractsWithOrganizationDashboard;

    protected string $view = 'filament.app.widgets.organization-task-breakdown';

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected static ?int $sort = 2;

    protected function getViewData(): array
    {
        $organization = $this->getDashboardOrganization();
        $tasksQuery = $this->getFilteredTasksQuery();
        $statuses = (clone $tasksQuery)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();
        $priorities = (clone $tasksQuery)
            ->selectRaw('priority, COUNT(*) as c')
            ->groupBy('priority')
            ->pluck('c', 'priority')
            ->all();
        $statusTotal = max(1, array_sum($statuses));
        $priorityTotal = max(1, array_sum($priorities));
        $plannedPoints = (clone $tasksQuery)->sum('story_points');

        return [
            'statusRows' => collect(['todo', 'in_progress', 'review', 'done'])->map(fn (string $status): array => [
                'label' => str_replace('_', ' ', $status),
                'count' => (int) ($statuses[$status] ?? 0),
                'percent' => round((($statuses[$status] ?? 0) / $statusTotal) * 100),
            ])->all(),
            'priorityRows' => collect(['low', 'med', 'high', 'crit'])->map(fn (string $priority): array => [
                'label' => $priority,
                'count' => (int) ($priorities[$priority] ?? 0),
                'percent' => round((($priorities[$priority] ?? 0) / $priorityTotal) * 100),
            ])->all(),
            'plannedPoints' => (int) $plannedPoints,
            'storyPointsTarget' => $organization?->storyPointsPerSprint() ?? 20,
        ];
    }
}
