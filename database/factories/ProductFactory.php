<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);
        $slug = Str::slug($title . '-' . fake()->unique()->numberBetween(100, 999));

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => $slug,
            'sku' => strtoupper(Str::random(3)) . '-' . fake()->unique()->numberBetween(10000, 99999),
            'description' => fake()->paragraphs(3, true),
            'price' => fake()->numberBetween(1800, 25000),
            'compare_at_price' => fake()->boolean(40) ? fake()->numberBetween(2000, 30000) : null,
            'stock' => fake()->numberBetween(10, 150),
            'status' => 'active',
            'metadata' => [
                'material' => fake()->randomElement(['Cotton', 'Silk', 'Denim', 'Leather', 'Linen', 'Polyester Blend']),
                'care' => fake()->randomElement(['Dry clean', 'Machine wash cold', 'Hand wash only']),
            ],
            'published_at' => now()->subDays(fake()->numberBetween(0, 120)),
        ];
    }
}
