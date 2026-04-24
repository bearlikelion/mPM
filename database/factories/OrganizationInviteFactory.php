<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationInvite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationInvite>
 */
class OrganizationInviteFactory extends Factory
{
    protected $model = OrganizationInvite::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => fake()->randomElement(['member', 'project_admin']),
            'expires_at' => fake()->dateTimeBetween('+3 days', '+3 weeks'),
        ];
    }
}
