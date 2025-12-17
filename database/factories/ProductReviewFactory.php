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
            'rating' => $this->faker->numberBetween(3, 5),
            'title' => $this->faker->sentence(6),
            'body' => $this->faker->paragraphs(2, true),
            'attributes' => [
                'fit' => $this->faker->randomElement(['Runs small', 'True to size', 'Runs large']),
                'quality' => $this->faker->randomElement(['Excellent', 'Good', 'Premium']),
            ],
        ];
    }
}
