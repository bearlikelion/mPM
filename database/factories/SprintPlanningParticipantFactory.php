<?php

namespace Database\Factories;

use App\Models\SprintPlanningMeeting;
use App\Models\SprintPlanningParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SprintPlanningParticipant>
 */
class SprintPlanningParticipantFactory extends Factory
{
    protected $model = SprintPlanningParticipant::class;

    public function definition(): array
    {
        return [
            'sprint_planning_meeting_id' => SprintPlanningMeeting::factory(),
            'user_id' => User::factory(),
            'joined_at' => now(),
            'last_seen_at' => now(),
        ];
    }
}
