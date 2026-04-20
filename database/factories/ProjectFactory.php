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
        $name = fake()->unique()->words(2, true);

        return [
            'organization_id' => Organization::factory(),
            'name' => ucwords($name),
            'key' => Str::upper(Str::random(4)),
            'description' => fake()->sentence(),
            'visibility' => Project::VISIBILITY_ORG,
            'task_counter' => 0,
        ];
    }
}
