<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\SprintResource\Pages;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Sprint;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SprintResource extends Resource
{
    protected static ?string $model = Sprint::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rocket-launch';

    protected static ?int $navigationSort = 3;

    protected static string|\UnitEnum|null $navigationGroup = 'Delivery';

    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        /** @var Organization|null $tenant */
        $tenant = Filament::getTenant();
        $sprintLengthDays = $tenant?->sprintLengthDays() ?? Organization::DEFAULT_SPRINT_LENGTH_DAYS;
        $storyPointsTarget = $tenant?->storyPointsPerSprint() ?? Organization::DEFAULT_STORY_POINTS_PER_SPRINT;

        return $schema->components([
            Select::make('project_id')
                ->label('Project')
                ->options(fn () => Project::query()->pluck('name', 'id'))
                ->required()
                ->searchable(),
            TextInput::make('name')->required()->maxLength(255),
            DatePicker::make('starts_at')
                ->required()
                ->default(now()->toDateString()),
            DatePicker::make('ends_at')
                ->required()
                ->after('starts_at')
                ->default(now()->addDays($sprintLengthDays - 1)->toDateString())
                ->helperText("Default sprint cadence: {$sprintLengthDays} days · target {$storyPointsTarget} pts."),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('project.name')->sortable(),
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('starts_at')->date()->sortable(),
            TextColumn::make('ends_at')->date()->sortable(),
            TextColumn::make('planned_points')
                ->label('Planned pts')
                ->state(fn (Sprint $record): int => (int) $record->tasks()->sum('story_points')),
            IconColumn::make('started_at')->boolean()->label('Started'),
            IconColumn::make('ended_at')->boolean()->label('Ended'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSprints::route('/'),
            'create' => Pages\CreateSprint::route('/create'),
            'edit' => Pages\EditSprint::route('/{record}/edit'),
        ];
    }
}
