<?php

namespace App\Filament\Admin\Widgets;

use App\Support\Analytics;
use Filament\Widgets\Widget;

class TaskBreakdownWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.task-breakdown';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 2;

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $statuses = Analytics::taskStatusCounts();
        $priorities = Analytics::taskPriorityCounts();

        $statusTotal = array_sum($statuses) ?: 1;
        $priorityTotal = array_sum($priorities) ?: 1;

        return [
            'statuses' => collect($statuses)->map(fn ($count, $key) => [
                'key' => $key,
                'label' => str_replace('_', ' ', $key),
                'count' => $count,
                'percent' => round($count / $statusTotal * 100),
            ])->values(),
            'priorities' => collect($priorities)->map(fn ($count, $key) => [
                'key' => $key,
                'label' => $key,
                'count' => $count,
                'percent' => round($count / $priorityTotal * 100),
            ])->values(),
        ];
    }
}
