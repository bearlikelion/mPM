<?php

namespace App\Support;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Analytics
{
    public static function totalStorageBytes(): int
    {
        return (int) DB::table('media')->sum('size');
    }

    public static function humanBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, $precision).' '.$units[$i];
    }

    /**
     * @return array<int, int> counts per day for the trailing $days days (oldest → newest)
     */
    public static function dailyCounts(Builder $query, int $days, string $column = 'created_at'): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = (clone $query)
            ->where($column, '>=', $start)
            ->selectRaw("DATE({$column}) as d, COUNT(*) as c")
            ->groupBy('d')
            ->pluck('c', 'd')
            ->all();

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->format('Y-m-d');
            $out[] = (int) ($rows[$date] ?? 0);
        }

        return $out;
    }

    public static function activeSprintsCount(): int
    {
        return Sprint::whereNotNull('started_at')->whereNull('ended_at')->count();
    }

    public static function taskStatusCounts(): array
    {
        $rows = Task::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status')->all();

        return [
            'todo' => (int) ($rows['todo'] ?? 0),
            'in_progress' => (int) ($rows['in_progress'] ?? 0),
            'review' => (int) ($rows['review'] ?? 0),
            'done' => (int) ($rows['done'] ?? 0),
        ];
    }

    public static function taskPriorityCounts(): array
    {
        $rows = Task::selectRaw('priority, COUNT(*) as c')->groupBy('priority')->pluck('c', 'priority')->all();

        return [
            'low' => (int) ($rows['low'] ?? 0),
            'med' => (int) ($rows['med'] ?? 0),
            'high' => (int) ($rows['high'] ?? 0),
            'crit' => (int) ($rows['crit'] ?? 0),
        ];
    }

    public static function completionRate(): float
    {
        $total = Task::count();
        if ($total === 0) {
            return 0.0;
        }

        return round(Task::where('status', 'done')->count() / $total * 100, 1);
    }

    public static function orgStorageBytes(int $organizationId): int
    {
        // Sum media attached to tasks, comments, and users within this org.
        $taskIds = Task::whereHas('project', fn ($q) => $q->where('organization_id', $organizationId))->pluck('id');
        $commentIds = Comment::whereIn('task_id', $taskIds)->pluck('id');
        $userIds = User::whereHas('organizations', fn ($q) => $q->whereKey($organizationId))->pluck('id');

        return (int) DB::table('media')
            ->where(function ($q) use ($taskIds, $commentIds, $userIds) {
                $q->where(function ($q) use ($taskIds) {
                    $q->where('model_type', Task::class)->whereIn('model_id', $taskIds);
                })->orWhere(function ($q) use ($commentIds) {
                    $q->where('model_type', Comment::class)->whereIn('model_id', $commentIds);
                })->orWhere(function ($q) use ($userIds) {
                    $q->where('model_type', User::class)->whereIn('model_id', $userIds);
                });
            })
            ->sum('size');
    }

    public static function orgLastActivityAt(int $organizationId): ?string
    {
        $latestTask = Task::whereHas('project', fn ($q) => $q->where('organization_id', $organizationId))
            ->max('updated_at');
        $latestComment = Comment::whereHas('task.project', fn ($q) => $q->where('organization_id', $organizationId))
            ->max('created_at');

        return collect([$latestTask, $latestComment])->filter()->max();
    }

    public static function topOrgsByActivity(int $days = 30, int $limit = 5): array
    {
        $since = now()->subDays($days);

        return Organization::query()
            ->select('organizations.id', 'organizations.name')
            ->selectSub(function ($q) use ($since) {
                $q->from('tasks')
                    ->selectRaw('COUNT(*)')
                    ->join('projects', 'projects.id', '=', 'tasks.project_id')
                    ->whereColumn('projects.organization_id', 'organizations.id')
                    ->where('tasks.created_at', '>=', $since);
            }, 'tasks_created')
            ->orderByDesc('tasks_created')
            ->limit($limit)
            ->get()
            ->map(fn ($o) => ['name' => $o->name, 'id' => $o->id, 'tasks_created' => (int) $o->tasks_created])
            ->all();
    }

    /**
     * @return array<int, array{name: string, id: int, bytes: int}>
     */
    public static function topOrgsByStorage(int $limit = 5): array
    {
        return Organization::query()
            ->get()
            ->map(fn (Organization $o) => [
                'id' => $o->id,
                'name' => $o->name,
                'bytes' => self::orgStorageBytes($o->id),
            ])
            ->sortByDesc('bytes')
            ->take($limit)
            ->values()
            ->all();
    }

    public static function newUsersCount(int $days): int
    {
        return User::where('created_at', '>=', now()->subDays($days))->count();
    }

    public static function newOrgsCount(int $days): int
    {
        return Organization::where('created_at', '>=', now()->subDays($days))->count();
    }

    public static function newTasksCount(int $days): int
    {
        return Task::where('created_at', '>=', now()->subDays($days))->count();
    }

    public static function newCommentsCount(int $days): int
    {
        return Comment::where('created_at', '>=', now()->subDays($days))->count();
    }
}
