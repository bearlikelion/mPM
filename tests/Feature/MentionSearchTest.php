<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentionSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_org_members_only(): void
    {
        $organization = Organization::factory()->create();
        $other = Organization::factory()->create();

        $viewer = User::factory()->create(['default_organization_id' => $organization->id]);
        $teammate = User::factory()->create(['name' => 'Avery Ng']);
        $stranger = User::factory()->create(['name' => 'Avery Outsider']);

        $organization->users()->attach([$viewer->id, $teammate->id]);
        $other->users()->attach($stranger);

        $response = $this->actingAs($viewer)
            ->getJson(route('mentions.search', ['q' => 'Avery', 'org' => $organization->id]));

        $response->assertOk();
        $names = collect($response->json())->pluck('name')->all();

        $this->assertContains('Avery Ng', $names);
        $this->assertNotContains('Avery Outsider', $names);
    }

    public function test_search_requires_authentication(): void
    {
        $this->getJson(route('mentions.search'))->assertStatus(401);
    }
}
