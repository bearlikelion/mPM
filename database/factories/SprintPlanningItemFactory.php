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
            'status' => SprintPlanningItem::STATUS_PENDING,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
