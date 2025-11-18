<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    public function definition(): array
    {
        $baseUrls = [
            'https://images.unsplash.com/photo-1521572267360-ee0c2909d518',
            'https://images.unsplash.com/photo-1541099649105-f69ad21f3246',
            'https://images.unsplash.com/photo-1524504388940-b1c1722653e1',
            'https://images.unsplash.com/photo-1542291026-7eec264c27ff',
            'https://images.unsplash.com/photo-1519751138087-5bf79df62d5b',
            'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab',
            'https://images.unsplash.com/photo-1512436991641-6745cdb1723f',
            'https://images.unsplash.com/photo-1490481651871-ab68de25d43d',
        ];

        $url = fake()->randomElement($baseUrls);

        return [
            'product_id' => Product::factory(),
            'url' => $url . '?auto=format&fit=crop&w=1200&q=80',
            'is_primary' => fake()->boolean(30),
            'position' => fake()->numberBetween(0, 6),
        ];
    }
}
