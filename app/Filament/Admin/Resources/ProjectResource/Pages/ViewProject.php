<?php

namespace App\Filament\Admin\Resources\ProjectResource\Pages;

use App\Filament\Admin\Resources\ProjectResource;
use App\Models\Comment;
use App\Models\Project;
use App\Support\Analytics;
use Filament\Resources\Pages\ViewRecord;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.admin.pages.view-project';

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        /** @var Project $project */
        $project = $this->record;

        $taskBase = $project->tasks()->getQuery();

        $statusCounts = (clone $taskBase)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $priorityCounts = (clone $taskBase)
            ->selectRaw('priority, COUNT(*) as c')
            ->groupBy('priority')
            ->pluck('c', 'priority')
            ->all();

        $statusTotal = max(1, array_sum($statusCounts));
        $priorityTotal = max(1, array_sum($priorityCounts));

        $trend = Analytics::dailyCounts($taskBase, 30);

        $activeSprint = $project->sprints()
            ->whereNotNull('started_at')->whereNull('ended_at')
            ->first();

        return [
            'project' => $project,
            'stats' => [
                'tasks' => (clone $taskBase)->count(),
                'tasks_open' => (clone $taskBase)->where('status', '!=', 'done')->count(),
                'tasks_done' => (clone $taskBase)->where('status', 'done')->count(),
                'tasks_30d' => (clone $taskBase)->where('created_at', '>=', now()->subDays(30))->count(),
                'comments_30d' => Comment::whereIn('task_id', (clone $taskBase)->pluck('id'))
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count(),
                'epics' => $project->epics()->count(),
                'epics_done' => $project->epics()->whereNotNull('completed_at')->count(),
                'members' => $project->members()->count(),
                'active_sprint' => $activeSprint?->name ?? '-',
                'completion_rate' => $statusTotal > 1
                    ? round(($statusCounts['done'] ?? 0) / $statusTotal * 100, 1)
                    : 0.0,
            ],
            'statusBars' => collect(['todo', 'in_progress', 'review', 'done'])->map(fn ($s) => [
                'label' => str_replace('_', ' ', $s),
                'count' => (int) ($statusCounts[$s] ?? 0),
                'percent' => round(($statusCounts[$s] ?? 0) / $statusTotal * 100),
            ]),
            'priorityBars' => collect(['low', 'med', 'high', 'crit'])->map(fn ($p) => [
                'label' => $p,
                'count' => (int) ($priorityCounts[$p] ?? 0),
                'percent' => round(($priorityCounts[$p] ?? 0) / $priorityTotal * 100),
            ]),
            'tasksTrend' => $trend,
            'tasksTrendMax' => max($trend) ?: 1,
            'epics' => $project->epics()
                ->withCount([
                    'tasks',
                    'tasks as tasks_done_count' => fn ($q) => $q->where('status', 'done'),
                ])
                ->orderByDesc('updated_at')
                ->get(),
            'sprints' => $project->sprints()
                ->withCount('tasks')
                ->orderByDesc('starts_at')
                ->limit(10)
                ->get(),
            'members' => $project->members()->withPivot('role')->orderBy('name')->get(),
        ];
    }
}
