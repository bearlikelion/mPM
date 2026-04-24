<?php

namespace Database\Factories;

use App\Models\Epic;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Epic>
 */
class EpicFactory extends Factory
{
    protected $model = Epic::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Customer onboarding refresh',
            'Reporting and insights',
            'Release readiness',
            'Team workflow cleanup',
            'Billing operations',
            'Mobile polish pass',
            'Planning room improvements',
            'Search and navigation',
        ]);

        return [
            'project_id' => Project::factory(),
            'name' => $name,
            'description' => fake()->randomElement([
                'Tighten the end-to-end experience so teams can move from intake to execution without manual handoffs.',
                'Expose the operational signals managers need without forcing them to inspect each board individually.',
                'Reduce friction in the recurring workflow and make edge cases visible before they become support requests.',
                'Prepare the project for a clean release by closing the highest-risk usability and reliability gaps.',
            ]),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+3 months'),
        ];
    }
}
