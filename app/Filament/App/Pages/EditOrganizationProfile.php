<?php

namespace App\Filament\App\Pages;

use App\Support\Timezones;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;

class EditOrganizationProfile extends EditTenantProfile
{
    protected static string|\UnitEnum|null $navigationGroup = 'Organization';

    protected static ?int $navigationSort = 90;

    public static function getLabel(): string
    {
        return 'Organization settings';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
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
            Toggle::make('registration_enabled')->label('Allow public registration into this org'),
        ]);
    }
}
