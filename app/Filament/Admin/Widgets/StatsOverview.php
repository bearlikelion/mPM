<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Epic;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Organizations', Organization::count())
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),
            Stat::make('Users', User::count())
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Projects', Project::count())
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),
            Stat::make('Tasks', Task::count())
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),
            Stat::make('Epics', Epic::count())
                ->descriptionIcon('heroicon-m-flag')
                ->color('primary'),
            Stat::make('Sprints', Sprint::count())
                ->descriptionIcon('heroicon-m-rocket-launch')
                ->color('primary'),
        ];
    }
}
