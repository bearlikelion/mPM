<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->randomElement([
                'bug',
                'customer-impact',
                'design',
                'docs',
                'feature',
                'ops',
                'qa',
                'security',
                'split-up',
                'tech-debt',
            ]).'-'.fake()->unique()->numberBetween(10, 99),
            'color' => fake()->randomElement([
                '#ef4444',
                '#f59e0b',
                '#84cc16',
                '#14b8a6',
                '#3b82f6',
                '#8b5cf6',
                '#ec4899',
                '#64748b',
            ]),
        ];
    }
}
