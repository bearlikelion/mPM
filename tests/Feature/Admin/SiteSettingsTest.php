<?php

use App\Filament\Admin\Pages\SiteSettings;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
    filament()->setCurrentPanel('admin');
});

it('creates a singleton row on first access', function () {
    expect(SiteSetting::count())->toBe(0);

    $settings = SiteSetting::current();

    expect(SiteSetting::count())->toBe(1)
        ->and($settings->registration_enabled)->toBeTrue()
        ->and($settings->org_creation_enabled)->toBeTrue()
        ->and($settings->org_invites_bypass_registration)->toBeTrue()
        ->and($settings->org_limit_per_user)->toBe(5)
        ->and($settings->user_limit_per_org)->toBe(50);

    SiteSetting::current();
    expect(SiteSetting::count())->toBe(1);
});

it('saves settings from the admin page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('site_admin');
    $this->actingAs($admin);

    Livewire::test(SiteSettings::class)
        ->set('data.registration_enabled', false)
        ->set('data.org_creation_enabled', false)
        ->set('data.org_invites_bypass_registration', false)
        ->set('data.org_limit_per_user', 2)
        ->set('data.user_limit_per_org', 10)
        ->call('save')
        ->assertHasNoErrors();

    $settings = SiteSetting::current();
    expect($settings->registration_enabled)->toBeFalse()
        ->and($settings->org_creation_enabled)->toBeFalse()
        ->and($settings->org_invites_bypass_registration)->toBeFalse()
        ->and($settings->org_limit_per_user)->toBe(2)
        ->and($settings->user_limit_per_org)->toBe(10);
});
