<?php

namespace App\Filament\App\Pages;

use App\Models\Organization;
use App\Models\SiteSetting;
use App\Support\Timezones;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RegisterOrganization extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Create organization';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('create', Organization::class) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
            TextInput::make('slug')
                ->required()
                ->maxLength(255),
            Select::make('timezone')
                ->options(Timezones::options())
                ->required()
                ->searchable()
                ->default(auth()->user()?->timezone ?: 'UTC'),
        ]);
    }

    protected function handleRegistration(array $data): Organization
    {
        $user = auth()->user();
        $settings = SiteSetting::current();

        abort_unless($user, 403);
        abort_unless($settings->org_creation_enabled, 403);
        abort_if($user->organizations()->count() >= $settings->org_limit_per_user, 403, 'Organization limit reached.');

        $organization = Organization::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['slug'] ?: $data['name']),
            'timezone' => $data['timezone'],
        ]);

        $organization->users()->attach($user, [
            'role' => 'org_admin',
            'joined_at' => now(),
        ]);

        if (! $user->default_organization_id) {
            $user->update(['default_organization_id' => $organization->id]);
        }

        return $organization;
    }

    private function uniqueSlug(string $value): string
    {
        $baseSlug = Str::slug($value);
        $slug = $baseSlug;
        $counter = 2;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
