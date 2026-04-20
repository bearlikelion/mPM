<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\SprintResource\Pages;
use App\Models\Project;
use App\Models\Sprint;
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

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('project_id')
                ->label('Project')
                ->options(fn () => Project::query()->pluck('name', 'id'))
                ->required()
                ->searchable(),
            TextInput::make('name')->required()->maxLength(255),
            DatePicker::make('starts_at')->required(),
            DatePicker::make('ends_at')->required()->after('starts_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('project.name')->sortable(),
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('starts_at')->date()->sortable(),
            TextColumn::make('ends_at')->date()->sortable(),
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
