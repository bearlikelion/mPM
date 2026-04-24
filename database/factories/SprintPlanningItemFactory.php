<?php

namespace Database\Factories;

use App\Models\SprintPlanningItem;
use App\Models\SprintPlanningMeeting;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SprintPlanningItem>
 */
class SprintPlanningItemFactory extends Factory
{
    protected $model = SprintPlanningItem::class;

    public function definition(): array
    {
        return [
            'sprint_planning_meeting_id' => SprintPlanningMeeting::factory(),
            'task_id' => Task::factory(),
            'status' => fake()->randomElement(SprintPlanningItem::STATUSES),
            'sort_order' => fake()->numberBetween(1, 20),
            'selected_story_points' => fake()->optional(0.55)->randomElement(Task::STORY_POINTS),
            'decided_at' => fake()->optional(0.45)->dateTimeBetween('-3 days', 'now'),
        ];
    }
}
