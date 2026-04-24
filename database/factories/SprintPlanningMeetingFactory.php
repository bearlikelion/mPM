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
            'name' => 'Sprint Planning '.fake()->numberBetween(1, 20),
            'status' => SprintPlanningMeeting::STATUS_SCHEDULED,
            'scheduled_at' => now()->addDay(),
            'story_points_limit' => 20,
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
