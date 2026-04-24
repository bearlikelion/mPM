<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Filament\Admin\Resources\UserResource\RelationManagers\OrganizationsRelationManager;
use App\Models\Comment;
use App\Models\User;
use App\Support\Analytics;
use App\Support\Timezones;
use Carbon\Carbon;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            Select::make('timezone')
                ->options(Timezones::options())
                ->searchable(),
            FileUpload::make('avatar_path')
                ->label('Avatar')
                ->image()
                ->avatar()
                ->disk('public')
                ->directory('user-avatars')
                ->visibility('public')
                ->maxSize(2048),
            TextInput::make('password')
                ->password()
                ->revealable()
                ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $operation): bool => $operation === 'create'),
            Select::make('roles')
                ->relationship('roles', 'name')
                ->multiple()
                ->preload()
                ->label('Site roles'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable()->toggleable(),
                TextColumn::make('organizations_count')
                    ->counts('organizations')
                    ->label('Orgs')
                    ->sortable(),
                TextColumn::make('open_tasks')
                    ->label('Open tasks')
                    ->state(fn (User $record) => $record->assignedTasks()->where('tasks.status', '!=', 'done')->count()),
                TextColumn::make('done_30d')
                    ->label('Done · 30d')
                    ->state(fn (User $record) => $record->assignedTasks()
                        ->where('tasks.status', 'done')
                        ->where('tasks.updated_at', '>=', now()->subDays(30))
                        ->count()),
                TextColumn::make('comments_30d')
                    ->label('Comments · 30d')
                    ->state(fn (User $record) => Comment::where('user_id', $record->id)
                        ->where('created_at', '>=', now()->subDays(30))
                        ->count()),
                TextColumn::make('storage')
                    ->label('Storage')
                    ->state(fn (User $record) => Analytics::humanBytes(Analytics::userStorageBytes($record->id))),
                TextColumn::make('last_active')
                    ->label('Last active')
                    ->state(function (User $record) {
                        $ts = Analytics::userLastActivityAt($record->id);

                        return $ts ? Carbon::parse($ts)->diffForHumans() : '-';
                    }),
                TextColumn::make('roles.name')->label('Roles')->badge()->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            OrganizationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
