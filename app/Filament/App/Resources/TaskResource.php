<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\TaskResource\Pages;
use App\Models\Epic;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 4;

    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('project_id')
                ->label('Project')
                ->options(fn () => Project::query()->pluck('name', 'id'))
                ->required()
                ->live()
                ->searchable(),
            TextInput::make('title')->required()->maxLength(255),
            Textarea::make('description')->rows(4),
            Select::make('epic_id')
                ->label('Epic')
                ->options(fn ($get) => Epic::query()->where('project_id', $get('project_id'))->pluck('name', 'id')),
            Select::make('sprint_id')
                ->label('Sprint')
                ->options(fn ($get) => Sprint::query()->where('project_id', $get('project_id'))->pluck('name', 'id')),
            Select::make('status')
                ->options(array_combine(Task::STATUSES, array_map('ucwords', str_replace('_', ' ', Task::STATUSES))))
                ->default('todo')
                ->required(),
            Select::make('priority')
                ->options(array_combine(Task::PRIORITIES, Task::PRIORITIES))
                ->default('med')
                ->required(),
            Select::make('story_points')
                ->options(array_combine(Task::STORY_POINTS, Task::STORY_POINTS))
                ->label('Story points'),
            Select::make('assignees')
                ->multiple()
                ->relationship('assignees', 'name', fn ($query) => $query->whereHas('organizations', fn ($q) => $q->whereKey(Filament::getTenant()?->id))),
            Select::make('tags')
                ->multiple()
                ->relationship('tags', 'name'),
            DatePicker::make('due_date'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->badge()->sortable(),
                TextColumn::make('title')->searchable()->limit(50),
                TextColumn::make('project.name')->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('priority')->badge()->color(fn ($state) => match ($state) {
                    'crit' => 'danger',
                    'high' => 'warning',
                    'med' => 'info',
                    default => 'gray',
                }),
                TextColumn::make('story_points')->label('Pts'),
                TextColumn::make('assignees.name')->badge()->label('Assignees'),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(array_combine(Task::STATUSES, Task::STATUSES)),
                SelectFilter::make('priority')->options(array_combine(Task::PRIORITIES, Task::PRIORITIES)),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
