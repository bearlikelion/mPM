<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SiteInviteResource\Pages;
use App\Models\SiteInvite;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SiteInviteResource extends Resource
{
    protected static ?string $model = SiteInvite::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Invite links';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')
                ->maxLength(255)
                ->helperText('Optional name for your reference (e.g. "Launch batch").'),
            TextInput::make('max_uses')
                ->numeric()
                ->minValue(1)
                ->helperText('Leave empty for unlimited uses.'),
            DateTimePicker::make('expires_at')
                ->helperText('Leave empty for no expiry.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->searchable()->placeholder('-'),
                TextColumn::make('token')
                    ->label('Link')
                    ->formatStateUsing(fn (SiteInvite $record) => $record->url())
                    ->copyable()
                    ->copyMessage('Invite URL copied')
                    ->limit(40),
                TextColumn::make('uses')
                    ->label('Uses')
                    ->state(fn (SiteInvite $record) => $record->max_uses
                        ? "{$record->used_count} / {$record->max_uses}"
                        : "{$record->used_count} / ∞"),
                TextColumn::make('expires_at')->dateTime()->placeholder('Never'),
                TextColumn::make('creator.name')->label('Created by')->placeholder('-'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('copy')
                    ->icon('heroicon-o-clipboard')
                    ->action(function (SiteInvite $record) {
                        Notification::make()
                            ->title('Invite URL')
                            ->body($record->url())
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteInvites::route('/'),
            'create' => Pages\CreateSiteInvite::route('/create'),
            'edit' => Pages\EditSiteInvite::route('/{record}/edit'),
        ];
    }
}
