<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = Volt::test('auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('timezone', 'America/New_York')
            ->set('organization_name', 'Test Org')
            ->set('organization_timezone', 'America/Chicago')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register');

        $response
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('America/New_York', $user->timezone);
        $this->assertNotNull($user->defaultOrganization);
        $this->assertSame('Test Org', $user->defaultOrganization->name);
        $this->assertSame('America/Chicago', $user->defaultOrganization->timezone);
        $this->assertTrue(
            Organization::where('name', 'Test Org')
                ->where('timezone', 'America/Chicago')
                ->exists()
        );
    }
}
