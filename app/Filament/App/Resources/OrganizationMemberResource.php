<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\OrganizationMemberResource\Pages;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationMemberResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Members';

    protected static string|\UnitEnum|null $navigationGroup = 'Organization';

    protected static ?int $navigationSort = 92;

    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Member')
                ->schema([
                    TextInput::make('name')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('email')
                        ->email()
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('timezone')
                        ->disabled()
                        ->dehydrated(false),
                    Select::make('organization_role')
                        ->label('Organization role')
                        ->options(static::organizationRoleOptions())
                        ->required(),
                ])
                ->columns(2),
            Section::make('Project assignments')
                ->description('Limit access to specific projects and decide whether this member is a project admin there.')
                ->schema([
                    Repeater::make('project_memberships')
                        ->label('Projects')
                        ->schema([
                            Select::make('project_id')
                                ->label('Project')
                                ->options(fn (): array => static::projectOptions())
                                ->required()
                                ->searchable(),
                            Select::make('role')
                                ->options(static::projectRoleOptions())
                                ->default('member')
                                ->required(),
                        ])
                        ->defaultItems(0)
                        ->columns(2)
                        ->addActionLabel('Assign project'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        /** @var Organization|null $tenant */
        $tenant = Filament::getTenant();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('organization_role')
                    ->label('Role')
                    ->badge()
                    ->state(fn (User $record): string => str_replace('_', ' ', $record->organizationRoleFor($tenant?->getKey() ?? 0) ?? 'member')),
                TextColumn::make('project_assignments')
                    ->label('Projects')
                    ->state(fn (User $record): int => $record->projects()
                        ->where('organization_id', $tenant?->getKey())
                        ->count()),
                TextColumn::make('open_tasks')
                    ->label('Open tasks')
                    ->state(fn (User $record): int => $record->assignedTasks()
                        ->whereHas('project', fn (Builder $query) => $query->where('organization_id', $tenant?->getKey()))
                        ->where('tasks.status', '!=', 'done')
                        ->count()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var Organization|null $tenant */
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->whereHas('organizations', fn (Builder $query) => $query->whereKey($tenant?->getKey()))
            ->with([
                'organizations' => fn ($query) => $query->whereKey($tenant?->getKey()),
            ]);
    }

    public static function canViewAny(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Organization && (auth()->user()?->can('update', $tenant) ?? false);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Organization && (auth()->user()?->can('update', $tenant) ?? false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizationMembers::route('/'),
            'edit' => Pages\EditOrganizationMember::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function organizationRoleOptions(): array
    {
        return [
            'org_admin' => 'Org admin',
            'project_admin' => 'Project admin',
            'member' => 'Member',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function projectRoleOptions(): array
    {
        return [
            'project_admin' => 'Project admin',
            'member' => 'Member',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function projectOptions(): array
    {
        /** @var Organization|null $tenant */
        $tenant = Filament::getTenant();

        return Project::query()
            ->where('organization_id', $tenant?->getKey())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
