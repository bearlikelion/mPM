<?php

use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\SiteInvite;
use App\Models\SiteSetting;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

it('allows registration when registration is enabled', function () {
    SiteSetting::current()->update(['registration_enabled' => true]);

    $this->get(route('register'))->assertOk();
});

it('blocks registration when disabled and no invite', function () {
    SiteSetting::current()->update(['registration_enabled' => false]);

    $this->get(route('register'))->assertForbidden();
});

it('allows registration with a valid site invite', function () {
    SiteSetting::current()->update(['registration_enabled' => false]);
    $invite = SiteInvite::create(['label' => 'test']);

    $this->get(route('register', ['invite' => $invite->token]))->assertOk();
});

it('blocks registration with an exhausted site invite', function () {
    SiteSetting::current()->update(['registration_enabled' => false]);
    $invite = SiteInvite::create(['max_uses' => 1, 'used_count' => 1]);

    $this->get(route('register', ['invite' => $invite->token]))->assertForbidden();
});

it('redirects org invite token to org invite flow', function () {
    SiteSetting::current()->update([
        'registration_enabled' => false,
        'org_invites_bypass_registration' => true,
    ]);
    $org = Organization::factory()->create();
    $invite = OrganizationInvite::create([
        'organization_id' => $org->id,
        'email' => 'new@example.com',
        'role' => 'member',
    ]);

    $response = $this->get(route('register', ['invite' => $invite->token]));
    $response->assertRedirect(route('invite.show', ['token' => $invite->token]));
});

it('blocks org invite bypass when toggle is off', function () {
    SiteSetting::current()->update([
        'registration_enabled' => false,
        'org_invites_bypass_registration' => false,
    ]);
    $org = Organization::factory()->create();
    $invite = OrganizationInvite::create([
        'organization_id' => $org->id,
        'email' => 'new@example.com',
        'role' => 'member',
    ]);

    $this->get(route('register', ['invite' => $invite->token]))->assertForbidden();
});
