<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\SprintPlanningMeeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SprintPlanningMeeting>
 */
class SprintPlanningMeetingFactory extends Factory
{
    protected $model = SprintPlanningMeeting::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'facilitator_id' => User::factory(),
            'name' => fake()->randomElement([
                'Sprint planning: intake triage',
                'Sprint planning: release candidate',
                'Sprint planning: customer feedback',
                'Sprint planning: platform cleanup',
                'Sprint planning: roadmap review',
            ]),
            'status' => SprintPlanningMeeting::STATUS_SCHEDULED,
            'scheduled_at' => fake()->dateTimeBetween('-3 days', '+10 days'),
            'story_points_limit' => fake()->randomElement([20, 30, 40]),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => SprintPlanningMeeting::STATUS_ACTIVE,
            'started_at' => now(),
        ]);
    }
}
