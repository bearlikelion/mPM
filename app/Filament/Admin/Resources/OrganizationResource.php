<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\OrganizationResource\Pages;
use App\Models\Organization;
use App\Models\Task;
use App\Support\Analytics;
use App\Support\Timezones;
use Carbon\Carbon;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Select::make('timezone')
                ->options(Timezones::options())
                ->required()
                ->searchable(),
            FileUpload::make('logo_path')
                ->label('Logo')
                ->image()
                ->avatar()
                ->disk('public')
                ->directory('org-logos')
                ->visibility('public')
                ->maxSize(2048),
            Toggle::make('registration_enabled')->label('Public registration allowed'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Members')
                    ->sortable(),
                TextColumn::make('projects_count')
                    ->counts('projects')
                    ->label('Projects')
                    ->sortable(),
                TextColumn::make('tasks_count')
                    ->label('Tasks')
                    ->state(fn (Organization $record) => Task::whereHas('project', fn ($q) => $q->where('organization_id', $record->id))->count()),
                TextColumn::make('tasks_open')
                    ->label('Open')
                    ->state(fn (Organization $record) => Task::whereHas('project', fn ($q) => $q->where('organization_id', $record->id))->where('status', '!=', 'done')->count()),
                TextColumn::make('tasks_30d')
                    ->label('Tasks 30d')
                    ->state(fn (Organization $record) => Task::whereHas('project', fn ($q) => $q->where('organization_id', $record->id))->where('created_at', '>=', now()->subDays(30))->count()),
                TextColumn::make('storage')
                    ->label('Storage')
                    ->state(fn (Organization $record) => Analytics::humanBytes(Analytics::orgStorageBytes($record->id))),
                TextColumn::make('last_activity')
                    ->label('Last activity')
                    ->state(function (Organization $record) {
                        $ts = Analytics::orgLastActivityAt($record->id);

                        return $ts ? Carbon::parse($ts)->diffForHumans() : '-';
                    }),
                IconColumn::make('registration_enabled')->boolean()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'view' => Pages\ViewOrganization::route('/{record}'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }
}
