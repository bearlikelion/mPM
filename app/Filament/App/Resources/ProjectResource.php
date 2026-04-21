<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static ?int $navigationSort = 1;

    protected static ?string $tenantOwnershipRelationshipName = 'organization';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('key')->required()->maxLength(16)
                ->helperText('Short uppercase prefix used for task keys (e.g. SURF → SURF-42).'),
            Select::make('visibility')
                ->options([
                    Project::VISIBILITY_ORG => 'Everyone in org',
                    Project::VISIBILITY_RESTRICTED => 'Restricted to members',
                    Project::VISIBILITY_PUBLIC => 'Public (appears on public roadmap)',
                ])
                ->default(Project::VISIBILITY_ORG)
                ->required(),
            Textarea::make('description')->rows(4),
            FileUpload::make('avatar_path')
                ->label('Project avatar')
                ->image()
                ->avatar()
                ->disk('public')
                ->directory('project-avatars')
                ->visibility('public')
                ->maxSize(2048),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->sortable()->badge(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('visibility')->badge(),
                TextColumn::make('tasks_count')->counts('tasks')->label('Tasks'),
                TextColumn::make('epics_count')->counts('epics')->label('Epics'),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
