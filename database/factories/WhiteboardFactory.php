<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Whiteboard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Whiteboard>
 */
class WhiteboardFactory extends Factory
{
    protected $model = Whiteboard::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'updated_by' => null,
            'data' => null,
        ];
    }
}
