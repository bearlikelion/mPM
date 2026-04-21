<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use App\Support\Analytics;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected string $view = 'filament.admin.pages.view-user';

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        /** @var User $user */
        $user = $this->record;

        $assignedBase = $user->assignedTasks();

        $statusCounts = (clone $assignedBase)
            ->selectRaw('tasks.status, COUNT(*) as c')
            ->groupBy('tasks.status')
            ->pluck('c', 'status')
            ->all();

        $statusTotal = max(1, array_sum($statusCounts));

        $trend = Analytics::dailyCounts(
            Comment::query()->where('user_id', $user->id),
            30,
        );

        return [
            'user' => $user,
            'orgs' => $user->organizations()->withPivot(['role', 'joined_at'])->orderBy('name')->get(),
            'recentComments' => Comment::with('task.project')
                ->where('user_id', $user->id)
                ->latest()
                ->limit(10)
                ->get(),
            'recentAssigned' => Task::with('project')
                ->whereHas('assignees', fn ($q) => $q->whereKey($user->id))
                ->latest('updated_at')
                ->limit(10)
                ->get(),
            'stats' => [
                'orgs' => $user->organizations()->count(),
                'assigned_total' => (clone $assignedBase)->count(),
                'assigned_open' => (clone $assignedBase)->where('tasks.status', '!=', 'done')->count(),
                'done_30d' => (clone $assignedBase)
                    ->where('tasks.status', 'done')
                    ->where('tasks.updated_at', '>=', now()->subDays(30))
                    ->count(),
                'comments_30d' => Comment::where('user_id', $user->id)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count(),
                'created_tasks' => Task::where('created_by', $user->id)->count(),
                'storage' => Analytics::humanBytes(Analytics::userStorageBytes($user->id)),
                'last_active' => Analytics::userLastActivityAt($user->id),
            ],
            'statusBars' => collect(['todo', 'in_progress', 'review', 'done'])->map(fn ($s) => [
                'label' => str_replace('_', ' ', $s),
                'count' => (int) ($statusCounts[$s] ?? 0),
                'percent' => round(($statusCounts[$s] ?? 0) / $statusTotal * 100),
            ]),
            'commentTrend' => $trend,
            'commentTrendMax' => max($trend) ?: 1,
        ];
    }
}
