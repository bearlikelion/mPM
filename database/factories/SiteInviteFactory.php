<?php

namespace Database\Factories;

use App\Models\SiteInvite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteInvite>
 */
class SiteInviteFactory extends Factory
{
    protected $model = SiteInvite::class;

    public function definition(): array
    {
        return [
            'label' => fake()->randomElement([
                'Early access cohort',
                'Partner onboarding',
                'Contractor workspace trial',
                'Product advisory invite',
                'Internal QA review',
            ]),
            'max_uses' => fake()->optional(0.75)->numberBetween(3, 25),
            'used_count' => fake()->numberBetween(0, 2),
            'expires_at' => fake()->optional(0.8)->dateTimeBetween('+1 week', '+2 months'),
            'created_by' => User::factory(),
        ];
    }
}
