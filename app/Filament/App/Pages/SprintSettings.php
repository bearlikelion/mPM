<?php

namespace App\Filament\App\Pages;

use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SprintSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'Sprint settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Organization';

    protected static ?int $navigationSort = 91;

    protected string $view = 'filament.app.pages.sprint-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Organization && (auth()->user()?->can('update', $tenant) ?? false);
    }

    public function mount(): void
    {
        /** @var Organization|null $tenant */
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Organization, 404);
        abort_unless(auth()->user()?->can('update', $tenant), 403);

        $this->form->fill($tenant->sprintSettings());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sprint defaults')
                    ->description('Control the default cadence and planning target used throughout this organization.')
                    ->schema([
                        TextInput::make('sprint_length_days')
                            ->label('Sprint length (days)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(90)
                            ->required(),
                        TextInput::make('story_points_per_sprint')
                            ->label('Story points per sprint')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(500)
                            ->required(),
                    ])
                    ->columns(2),
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
        /** @var Organization $tenant */
        $tenant = Filament::getTenant();
        $state = $this->form->getState();

        $tenant->update([
            'settings' => array_merge($tenant->settings ?? [], [
                'sprint_length_days' => (int) $state['sprint_length_days'],
                'story_points_per_sprint' => (int) $state['story_points_per_sprint'],
            ]),
        ]);

        Notification::make()
            ->title('Sprint settings saved')
            ->success()
            ->send();
    }
}
