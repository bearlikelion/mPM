<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Support\Analytics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $userTrend = Analytics::dailyCounts(User::query(), 30);
        $orgTrend = Analytics::dailyCounts(Organization::query(), 30);
        $taskTrend = Analytics::dailyCounts(Task::query(), 30);
        $commentTrend = Analytics::dailyCounts(Comment::query(), 30);

        return [
            Stat::make('Users', User::count())
                ->description(Analytics::newUsersCount(30).' new · 30d')
                ->descriptionIcon('heroicon-m-users')
                ->chart($userTrend)
                ->color('primary'),

            Stat::make('Organizations', Organization::count())
                ->description(Analytics::newOrgsCount(30).' new · 30d')
                ->descriptionIcon('heroicon-m-building-office')
                ->chart($orgTrend)
                ->color('primary'),

            Stat::make('Storage used', Analytics::humanBytes(Analytics::totalStorageBytes()))
                ->description('total media attached')
                ->descriptionIcon('heroicon-m-cloud-arrow-up')
                ->color('primary'),

            Stat::make('Tasks', Task::count())
                ->description(Analytics::newTasksCount(30).' created · 30d')
                ->descriptionIcon('heroicon-m-check-circle')
                ->chart($taskTrend)
                ->color('primary'),

            Stat::make('Comments', Comment::count())
                ->description(Analytics::newCommentsCount(30).' · 30d')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->chart($commentTrend)
                ->color('primary'),

            Stat::make('Active sprints', Analytics::activeSprintsCount())
                ->description(Analytics::completionRate().'% tasks done overall')
                ->descriptionIcon('heroicon-m-rocket-launch')
                ->color('primary'),
        ];
    }
}
