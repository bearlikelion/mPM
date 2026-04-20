<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sprint>
 */
class SprintFactory extends Factory
{
    protected $model = Sprint::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 week', '+1 week');
        $end = (clone $start)->modify('+2 weeks');

        return [
            'project_id' => Project::factory(),
            'name' => 'Sprint '.fake()->numberBetween(1, 50),
            'starts_at' => $start,
            'ends_at' => $end,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'started_at' => now(),
            'ended_at' => null,
        ]);
    }
}
