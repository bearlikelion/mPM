<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\TagResource\Pages;
use App\Models\Tag;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 5;

    protected static ?string $tenantOwnershipRelationshipName = 'organization';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            ColorPicker::make('color')->default('#888888'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            ColorColumn::make('color'),
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('tasks_count')->counts('tasks')->label('Tasks'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTags::route('/'),
        ];
    }
}
