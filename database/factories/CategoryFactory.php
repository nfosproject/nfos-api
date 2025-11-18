<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(asText: true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name . '-' . fake()->unique()->randomNumber()),
            'display_order' => fake()->numberBetween(0, 50),
        ];
    }
}
