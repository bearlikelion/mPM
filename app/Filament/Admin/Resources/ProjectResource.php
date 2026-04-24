<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProjectResource\Pages;
use App\Models\Project;
use Carbon\Carbon;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')->label('Org')->sortable()->searchable(),
                TextColumn::make('key')->badge()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('visibility')->badge(),
                TextColumn::make('members_count')->counts('members')->label('Members'),
                TextColumn::make('tasks_count')->counts('tasks')->label('Tasks'),
                TextColumn::make('tasks_open_count')
                    ->counts([
                        'tasks as tasks_open_count' => fn ($q) => $q->where('status', '!=', 'done'),
                    ])
                    ->label('Open'),
                TextColumn::make('epics_count')->counts('epics')->label('Epics'),
                TextColumn::make('sprints_count')->counts('sprints')->label('Sprints'),
                TextColumn::make('last_activity')
                    ->label('Last activity')
                    ->state(function (Project $record) {
                        $latest = $record->tasks()->max('updated_at') ?? $record->updated_at;

                        return $latest ? Carbon::parse($latest)->diffForHumans() : '-';
                    }),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('visibility')
                    ->options([
                        Project::VISIBILITY_ORG => 'Org',
                        Project::VISIBILITY_RESTRICTED => 'Restricted',
                        Project::VISIBILITY_PUBLIC => 'Public',
                    ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'view' => Pages\ViewProject::route('/{record}'),
        ];
    }
}
