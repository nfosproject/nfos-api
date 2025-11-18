<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductReview>
 */
class ProductReviewFactory extends Factory
{
    protected $model = ProductReview::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->numberBetween(3, 5),
            'title' => fake()->sentence(6),
            'body' => fake()->paragraphs(2, true),
            'attributes' => [
                'fit' => fake()->randomElement(['Runs small', 'True to size', 'Runs large']),
                'quality' => fake()->randomElement(['Excellent', 'Good', 'Premium']),
            ],
        ];
    }
}
