<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'body' => fake()->randomElement([
                'I checked the latest build and the main flow is working. The remaining risk is around the empty-state copy.',
                'This is ready for review once the acceptance criteria are linked back to the project brief.',
                'I found one edge case during testing. We should cover the rollback path before moving this to done.',
                'The implementation looks good locally. I added notes for follow-up validation with production-like data.',
                'Can we split the reporting piece into a follow-up task? The core workflow is already shippable.',
                'Blocked until the design handoff includes final labels for the confirmation state.',
            ]),
        ];
    }
}
