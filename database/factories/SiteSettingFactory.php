<?php

namespace Database\Factories;

use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteSetting>
 */
class SiteSettingFactory extends Factory
{
    protected $model = SiteSetting::class;

    public function definition(): array
    {
        return [
            'registration_enabled' => fake()->boolean(70),
            'org_creation_enabled' => fake()->boolean(85),
            'org_invites_bypass_registration' => true,
            'org_limit_per_user' => fake()->numberBetween(3, 8),
            'user_limit_per_org' => fake()->numberBetween(25, 150),
        ];
    }
}
