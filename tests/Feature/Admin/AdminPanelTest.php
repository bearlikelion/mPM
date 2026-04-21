<?php

use App\Filament\Admin\Resources\UserResource\Pages\ListUsers;
use App\Filament\Admin\Widgets\StatsOverview;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
    filament()->setCurrentPanel('admin');
});

it('shows users in the admin UserResource list', function () {
    $admin = User::factory()->create();
    $admin->assignRole('site_admin');
    $other = User::factory()->create(['name' => 'Some Person']);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$admin, $other]);
});

it('renders stats overview widget with org and user counts', function () {
    $admin = User::factory()->create();
    $admin->assignRole('site_admin');
    Organization::factory()->count(3)->create();

    $this->actingAs($admin);

    Livewire::test(StatsOverview::class)
        ->assertSeeText('Organizations')
        ->assertSeeText('Users')
        ->assertSeeText('3');
});

it('blocks non-admins from the admin panel', function () {
    $user = User::factory()->create();
    expect($user->canAccessPanel(
        filament()->getPanel('admin')
    ))->toBeFalse();
});
