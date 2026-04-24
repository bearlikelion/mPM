<?php

namespace Database\Factories;

use App\Models\SprintPlanningItem;
use App\Models\SprintPlanningVote;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SprintPlanningVote>
 */
class SprintPlanningVoteFactory extends Factory
{
    protected $model = SprintPlanningVote::class;

    public function definition(): array
    {
        return [
            'sprint_planning_item_id' => SprintPlanningItem::factory(),
            'user_id' => User::factory(),
            'story_points' => fake()->randomElement(Task::STORY_POINTS),
        ];
    }
}
