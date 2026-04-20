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
        return [
            'project_id' => Project::factory(),
            'name' => ucfirst(fake()->words(3, true)),
            'description' => fake()->paragraph(),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+3 months'),
        ];
    }
}
