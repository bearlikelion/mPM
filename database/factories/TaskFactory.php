<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $title = fake()->randomElement([
            'Add empty state for project dashboard',
            'Audit invite acceptance edge cases',
            'Connect sprint summary metrics',
            'Create regression checklist for release',
            'Document onboarding handoff steps',
            'Fix blocked task ordering on board',
            'Improve notification copy for assignees',
            'Review mobile spacing on task detail',
            'Split oversized planning item',
            'Validate organization switch flow',
        ]);

        return [
            'project_id' => Project::factory(),
            'key' => 'TEMP-'.fake()->unique()->numberBetween(1, 99999),
            'title' => $title,
            'description' => fake()->randomElement([
                'User story: As a project member, I need this workflow to be clear enough that I can complete it without asking an admin for context.',
                'Acceptance criteria: the happy path works, error states explain the next step, and the UI remains readable on mobile and desktop.',
                'Context: this came out of the last planning review and should reduce repeated manual follow-up from the project owner.',
                'Implementation notes: keep the scope narrow, preserve existing permissions, and add coverage for the highest-risk branch.',
                'Validation: test with an org admin, a project member, and a user with access to more than one organization.',
            ]),
            'status' => fake()->randomElement(Task::STATUSES),
            'priority' => fake()->randomElement(Task::PRIORITIES),
            'story_points' => fake()->optional(0.85)->randomElement(Task::STORY_POINTS),
            'due_date' => fake()->optional(0.65)->dateTimeBetween('now', '+1 month'),
        ];
    }
}
