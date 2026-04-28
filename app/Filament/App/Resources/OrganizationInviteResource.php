<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\OrganizationInviteResource\Pages;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use Filament\Actions\Action as TableAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class OrganizationInviteResource extends Resource
{
    protected static ?string $model = OrganizationInvite::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope-open';

    protected static ?string $navigationLabel = 'Invites';

    protected static string|\UnitEnum|null $navigationGroup = 'Organization';

    protected static ?int $navigationSort = 93;

    protected static ?string $tenantOwnershipRelationshipName = 'organization';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')
                ->email()
                ->required()
                ->disabledOn('edit'),
            Select::make('role')
                ->options(OrganizationMemberResource::organizationRoleOptions())
                ->default('member')
                ->required(),
            DateTimePicker::make('expires_at')
                ->default(now()->addDays(7))
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', $state)),
                TextColumn::make('status')
                    ->badge()
                    ->state(fn (OrganizationInvite $record): string => $record->accepted_at ? 'Accepted' : ($record->isExpired() ? 'Expired' : 'Pending'))
                    ->color(fn (string $state): string => match ($state) {
                        'Accepted' => 'success',
                        'Expired' => 'danger',
                        'Pending' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('token')
                    ->label('Invite Link')
                    ->formatStateUsing(fn (OrganizationInvite $record) => $record->url())
                    ->copyable()
                    ->copyableState(fn (OrganizationInvite $record) => $record->url())
                    ->copyMessage('Invite link copied')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('inviter.name')
                    ->label('Invited by')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                TableAction::make('copy_link')
                    ->label('Copy link')
                    ->icon('heroicon-m-clipboard-document')
                    ->color('gray')
                    ->action(function (OrganizationInvite $record) {
                        Notification::make()
                            ->title('Invite link copied')
                            ->body($record->url())
                            ->success()
                            ->send();
                    })
                    ->alpineClickHandler(fn (OrganizationInvite $record) => "window.navigator.clipboard.writeText('{$record->url()}');"),
                TableAction::make('resend')
                    ->label('Resend')
                    ->icon('heroicon-m-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function (OrganizationInvite $record) {
                        $record->update([
                            'token' => Str::random(48),
                            'expires_at' => now()->addDays(7),
                        ]);

                        Notification::make()
                            ->title('Invite link refreshed')
                            ->body($record->url())
                            ->success()
                            ->send();
                    })
                    ->visible(fn (OrganizationInvite $record) => ! $record->accepted_at),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizationInvites::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Organization && (auth()->user()?->can('update', $tenant) ?? false);
    }

    public static function canViewAny(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Organization && (auth()->user()?->can('update', $tenant) ?? false);
    }
}
