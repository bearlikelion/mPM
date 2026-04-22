<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Widgets\OrganizationProjectLoadWidget;
use App\Filament\App\Widgets\OrganizationStatsOverview;
use App\Filament\App\Widgets\OrganizationTaskBreakdownWidget;
use App\Filament\App\Widgets\OrganizationTeamLoadWidget;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Overview';

    public function filtersForm(Schema $schema): Schema
    {
        $tenant = Filament::getTenant();

        return $schema->components([
            Section::make()
                ->schema([
                    Select::make('project_id')
                        ->label('Project')
                        ->options(fn (): array => Project::query()
                            ->where('organization_id', $tenant?->getKey())
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->placeholder('All projects')
                        ->searchable(),
                    Select::make('window_days')
                        ->label('Window')
                        ->options([
                            7 => 'Last 7 days',
                            30 => 'Last 30 days',
                            90 => 'Last 90 days',
                        ])
                        ->default(30)
                        ->native(false),
                ])
                ->columns(2),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            OrganizationStatsOverview::class,
            OrganizationTaskBreakdownWidget::class,
            OrganizationProjectLoadWidget::class,
            OrganizationTeamLoadWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 6,
        ];
    }
}
