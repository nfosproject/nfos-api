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
        $title = $this->faker->unique()->sentence(3);
        $slug = Str::slug($title . '-' . $this->faker->unique()->numberBetween(100, 999));

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => $slug,
            'sku' => strtoupper(Str::random(3)) . '-' . $this->faker->unique()->numberBetween(10000, 99999),
            'description' => $this->faker->paragraphs(3, true),
            'price' => $this->faker->numberBetween(1800, 25000),
            'compare_at_price' => $this->faker->boolean(40) ? $this->faker->numberBetween(2000, 30000) : null,
            'stock' => $this->faker->numberBetween(10, 150),
            'status' => 'active',
            'metadata' => [
                'material' => $this->faker->randomElement(['Cotton', 'Silk', 'Denim', 'Leather', 'Linen', 'Polyester Blend']),
                'care' => $this->faker->randomElement(['Dry clean', 'Machine wash cold', 'Hand wash only']),
            ],
            'published_at' => now()->subDays($this->faker->numberBetween(0, 120)),
        ];
    }
}
