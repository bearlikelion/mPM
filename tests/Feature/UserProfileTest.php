<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_org_members_can_view_a_user_profile(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Nerdibear',
        ]);
        $viewer = User::factory()->create([
            'default_organization_id' => $organization->id,
        ]);
        $profileUser = User::factory()->create([
            'name' => 'Taylor Team',
        ]);

        $organization->users()->attach($viewer, [
            'role' => 'org_admin',
            'joined_at' => now(),
        ]);
        $organization->users()->attach($profileUser, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->get(route('users.show', $profileUser))
            ->assertOk()
            ->assertSee('Taylor Team')
            ->assertSee(route('kanban', ['assignee' => $profileUser->id]), false);
    }

    public function test_users_cannot_view_profiles_for_people_outside_their_orgs(): void
    {
        $viewerOrg = Organization::factory()->create();
        $otherOrg = Organization::factory()->create();

        $viewer = User::factory()->create([
            'default_organization_id' => $viewerOrg->id,
        ]);
        $profileUser = User::factory()->create();

        $viewerOrg->users()->attach($viewer, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
        $otherOrg->users()->attach($profileUser, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->get(route('users.show', $profileUser))
            ->assertForbidden();
    }
}
