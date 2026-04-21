<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\EpicResource\Pages;
use App\Models\Epic;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EpicResource extends Resource
{
    protected static ?string $model = Epic::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('project_id')
                ->label('Project')
                ->options(fn () => Project::query()->pluck('name', 'id'))
                ->required()
                ->searchable(),
            TextInput::make('name')->required()->maxLength(255),
            Textarea::make('description')->rows(3),
            FileUpload::make('avatar_path')
                ->label('Epic avatar')
                ->image()
                ->avatar()
                ->disk('public')
                ->directory('epic-avatars')
                ->visibility('public')
                ->maxSize(2048),
            DatePicker::make('due_date'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('project.name')->sortable(),
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('tasks_count')->counts('tasks')->label('Tasks'),
            TextColumn::make('due_date')->date()->sortable(),
            TextColumn::make('completed_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEpics::route('/'),
            'create' => Pages\CreateEpic::route('/create'),
            'edit' => Pages\EditEpic::route('/{record}/edit'),
        ];
    }
}
