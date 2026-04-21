<?php

namespace App\Filament\Admin\Resources\OrganizationResource\Pages;

use App\Filament\Admin\Resources\OrganizationResource;
use App\Models\Comment;
use App\Models\Organization;
use App\Models\Task;
use App\Support\Analytics;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrganization extends ViewRecord
{
    protected static string $resource = OrganizationResource::class;

    protected string $view = 'filament.admin.pages.view-organization';

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        /** @var Organization $org */
        $org = $this->record;

        $projectIds = $org->projects()->pluck('id');
        $taskBase = Task::whereIn('project_id', $projectIds);

        $statusCounts = (clone $taskBase)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $statusTotal = max(1, array_sum($statusCounts));

        $tasksTrend = Analytics::dailyCounts($taskBase, 30);

        return [
            'org' => $org,
            'members' => $org->users()->withPivot(['role', 'joined_at'])->orderBy('name')->get(),
            'projects' => $org->projects()
                ->withCount([
                    'tasks',
                    'tasks as tasks_open_count' => fn ($q) => $q->where('status', '!=', 'done'),
                    'tasks as tasks_done_count' => fn ($q) => $q->where('status', 'done'),
                    'members',
                ])
                ->orderByDesc('updated_at')
                ->get(),
            'invites' => $org->invites()->latest()->limit(10)->get(),
            'stats' => [
                'members' => $org->users()->count(),
                'projects' => $projectIds->count(),
                'tasks' => (clone $taskBase)->count(),
                'tasks_open' => (clone $taskBase)->where('status', '!=', 'done')->count(),
                'tasks_done' => (clone $taskBase)->where('status', 'done')->count(),
                'tasks_30d' => (clone $taskBase)->where('created_at', '>=', now()->subDays(30))->count(),
                'comments_30d' => Comment::whereIn('task_id', (clone $taskBase)->pluck('id'))
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count(),
                'storage' => Analytics::humanBytes(Analytics::orgStorageBytes($org->id)),
                'last_activity' => Analytics::orgLastActivityAt($org->id),
                'completion_rate' => $statusTotal > 1
                    ? round(($statusCounts['done'] ?? 0) / $statusTotal * 100, 1)
                    : 0.0,
            ],
            'statusBars' => collect(['todo', 'in_progress', 'review', 'done'])->map(fn ($s) => [
                'key' => $s,
                'label' => str_replace('_', ' ', $s),
                'count' => (int) ($statusCounts[$s] ?? 0),
                'percent' => round(($statusCounts[$s] ?? 0) / $statusTotal * 100),
            ]),
            'tasksTrend' => $tasksTrend,
            'tasksTrendMax' => max($tasksTrend) ?: 1,
        ];
    }
}
