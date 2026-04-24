<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Atlas Operations',
            'Beacon Portal',
            'Cascade Mobile',
            'Delta Analytics',
            'Harbor CRM',
            'Launch Control',
            'Northstar API',
            'Pulse Dashboard',
            'Signal Desk',
            'Waypoint Studio',
        ]).' '.fake()->unique()->numberBetween(100, 999);

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'key' => Str::upper(fake()->unique()->lexify('???')),
            'description' => fake()->randomElement([
                'Coordinate product delivery, support feedback, and release planning in one visible workspace.',
                'Track the customer-facing work needed for a stable launch and cleaner weekly planning.',
                'Bring scattered operations tasks into a single board with clear ownership and due dates.',
                'Collect roadmap work, bugs, and stakeholder requests for the next product milestone.',
            ]),
            'visibility' => fake()->randomElement([
                Project::VISIBILITY_ORG,
                Project::VISIBILITY_ORG,
                Project::VISIBILITY_RESTRICTED,
            ]),
            'task_counter' => 0,
        ];
    }
}
