<?php

namespace App\Filament\Admin\Pages;

use App\Models\SiteSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Site settings';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.admin.pages.site-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SiteSetting::current()->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Registration')
                    ->description('Control who may create new accounts and organizations.')
                    ->schema([
                        Toggle::make('registration_enabled')
                            ->label('Public registration enabled')
                            ->helperText('When off, new users cannot self-register without a valid invite.'),
                        Toggle::make('org_creation_enabled')
                            ->label('Users may create organizations'),
                        Toggle::make('org_invites_bypass_registration')
                            ->label('Org invites bypass registration lock')
                            ->helperText('If on, organization invite links allow signup even when public registration is disabled.'),
                    ]),
                Section::make('Limits')
                    ->schema([
                        TextInput::make('org_limit_per_user')
                            ->label('Max organizations per user')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        TextInput::make('user_limit_per_org')
                            ->label('Max users per organization')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save settings')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        SiteSetting::current()->update($this->form->getState());

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
