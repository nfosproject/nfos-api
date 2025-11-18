<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'EU 38', 'EU 40', 'EU 42'];
        $colors = ['Black', 'Ivory', 'Sage', 'Terracotta', 'Navy', 'Charcoal', 'Sand'];

        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(Str::random(4)) . fake()->unique()->numberBetween(1000, 9999),
            'title' => fake()->randomElement(['Standard fit', 'Slim fit', 'Relaxed fit', 'Tall fit']),
            'size' => fake()->randomElement($sizes),
            'color' => fake()->randomElement($colors),
            'price' => fake()->numberBetween(1800, 26000),
            'stock' => fake()->numberBetween(5, 120),
            'attributes' => [
                'material' => fake()->randomElement(['Organic cotton', 'Merino wool', 'Cashmere blend', 'Recycled polyester']),
            ],
        ];
    }
}
