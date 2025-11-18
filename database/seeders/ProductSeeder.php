<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Admin account for dashboard login
            User::firstOrCreate(
                ['email' => 'admin@nfos.test'],
                [
                    'name' => 'MERZi Admin',
                    'phone' => '+977-9800000000',
                    'role' => 'admin',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                ],
            );

            $existingSellerCount = User::where('role', 'seller')->count();
            $sellerTarget = 8;
            $sellers = User::where('role', 'seller')->get();
            if ($existingSellerCount < $sellerTarget) {
                User::factory()->count($sellerTarget - $existingSellerCount)->state(fn () => [
                    'role' => 'seller',
                ])->create();
                $sellers = User::where('role', 'seller')->get();
            }

            $existingCustomerCount = User::where('role', 'customer')->count();
            $customerTarget = 35;
            $customers = User::where('role', 'customer')->get();
            if ($existingCustomerCount < $customerTarget) {
                User::factory()->count($customerTarget - $existingCustomerCount)->state(fn () => [
                    'role' => 'customer',
                ])->create();
                $customers = User::where('role', 'customer')->get();
            }

            $categoryDefinitions = [
                [
                    'name' => 'Women',
                    'slug' => 'women',
                    'keywords' => ['Wrap Dress', 'Midi Dress', 'Kurta Set', 'Co-ord', 'Saree', 'Blazer Dress'],
                    'adjectives' => ['Heritage', 'Modern', 'Silk', 'Minimalist', 'Statement', 'Effortless'],
                    'sizes' => ['XS', 'S', 'M', 'L', 'XL'],
                ],
                [
                    'name' => 'Men',
                    'slug' => 'men',
                    'keywords' => ['Utility Jacket', 'Oxford Shirt', 'Chino', 'Denim Jacket', 'Polo', 'Layered Hoodie'],
                    'adjectives' => ['Vintage', 'Tailored', 'Everyday', 'Summit', 'Heritage', 'Iconic'],
                    'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
                ],
                [
                    'name' => 'Footwear',
                    'slug' => 'footwear',
                    'keywords' => ['Trail Sneaker', 'Leather Oxford', 'City Trainer', 'Chelsea Boot', 'Mule', 'Sandals'],
                    'adjectives' => ['Alpine', 'City', 'Classic', 'Performance', 'Heritage', 'Artisan'],
                    'sizes' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43'],
                ],
                [
                    'name' => 'Accessories',
                    'slug' => 'accessories',
                    'keywords' => ['Layered Necklace', 'Bracelet Set', 'Silk Scarf', 'Statement Earrings', 'Leather Belt'],
                    'adjectives' => ['Artisanal', 'Handloom', 'Sculpted', 'Minimal', 'Heritage', 'Luxe'],
                    'sizes' => ['One Size'],
                ],
                [
                    'name' => 'Tops',
                    'slug' => 'tops',
                    'keywords' => ['Pleated Blouse', 'Relaxed Tee', 'Crop Shirt', 'Mock Neck', 'Linen Shirt'],
                    'adjectives' => ['Airy', 'Structured', 'Essential', 'Elevated', 'Ribbed', 'Textured'],
                    'sizes' => ['XS', 'S', 'M', 'L', 'XL'],
                ],
                [
                    'name' => 'Pants',
                    'slug' => 'pants',
                    'keywords' => ['Wide-Leg Pant', 'Tailored Trouser', 'Paperbag Pant', 'Cargo Pant', 'Jogger'],
                    'adjectives' => ['Draped', 'Relaxed', 'Utility', 'Essential', 'Sculpted', 'Textured'],
                    'sizes' => ['26', '28', '30', '32', '34', '36'],
                ],
                [
                    'name' => 'Bags',
                    'slug' => 'bags',
                    'keywords' => ['Canvas Weekender', 'Bucket Bag', 'Crossbody', 'Tote', 'Satchel'],
                    'adjectives' => ['Voyager', 'Heritage', 'Contemporary', 'Studio', 'Artisan', 'Nomad'],
                    'sizes' => ['One Size'],
                ],
                [
                    'name' => 'Activewear',
                    'slug' => 'activewear',
                    'keywords' => ['Performance Legging', 'Trail Jacket', 'Support Bra', 'Running Short', 'Thermal Crew'],
                    'adjectives' => ['Peak', 'Trail', 'Momentum', 'Summit', 'Studio', 'Pulse'],
                    'sizes' => ['XS', 'S', 'M', 'L', 'XL'],
                ],
                [
                    'name' => 'Outerwear',
                    'slug' => 'outerwear',
                    'keywords' => ['Parka', 'Puffer', 'Trench Coat', 'Bomber', 'Wool Coat'],
                    'adjectives' => ['Element', 'Alpine', 'Elevated', 'Structured', 'Heritage', 'Luxe'],
                    'sizes' => ['S', 'M', 'L', 'XL'],
                ],
                [
                    'name' => 'Jewellery',
                    'slug' => 'jewellery',
                    'keywords' => ['Gemstone Ring', 'Pendant Necklace', 'Statement Cuff', 'Ear Climber', 'Anklet'],
                    'adjectives' => ['Handcrafted', 'Gilded', 'Minimalist', 'Celestial', 'Art Deco', 'Heritage'],
                    'sizes' => ['One Size'],
                ],
            ];

            $categories = collect($categoryDefinitions)->mapWithKeys(function (array $definition, int $index) {
                $category = Category::firstOrCreate(
                    ['slug' => $definition['slug']],
                    [
                        'name' => $definition['name'],
                        'display_order' => $index,
                    ],
                );

                return [$definition['slug'] => array_merge($definition, ['model' => $category])];
            });

            $imageLibrary = [
                'women' => [
                    'https://images.unsplash.com/photo-1503341455253-b2e723bb3dbb',
                    'https://images.unsplash.com/photo-1524504388940-b1c1722653e1',
                    'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab',
                    'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee',
                    'https://images.unsplash.com/photo-1521571753145-434c7c1cb17d',
                ],
                'men' => [
                    'https://images.unsplash.com/photo-1514756331096-242fdeb70d4a',
                    'https://images.unsplash.com/photo-1475180098004-ca77a66827be',
                    'https://images.unsplash.com/photo-1542291026-7eec264c27ff',
                    'https://images.unsplash.com/photo-1516826439464-dad55b04f38c',
                    'https://images.unsplash.com/photo-1489980557514-251d61e3eeb6',
                ],
                'footwear' => [
                    'https://images.unsplash.com/photo-1542293787938-4d2226c6c665',
                    'https://images.unsplash.com/photo-1485965120184-e220f721d03e',
                    'https://images.unsplash.com/photo-1595341888016-a392ef81b7de',
                    'https://images.unsplash.com/photo-1491553895911-0055eca6402d',
                    'https://images.unsplash.com/photo-1504593811423-6dd665756598',
                ],
                'outerwear' => [
                    'https://images.unsplash.com/photo-1517677129300-07b130802f46',
                    'https://images.unsplash.com/photo-1457972729786-0411a3b2b626',
                    'https://images.unsplash.com/photo-1512436991641-6745cdb1723f',
                    'https://images.unsplash.com/photo-1542365882-3a4b0d81c02a',
                    'https://images.unsplash.com/photo-1467043153537-a4f86fcd9892',
                ],
                'tops' => [
                    'https://images.unsplash.com/photo-1520430098645-7aaad2233f23',
                    'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab',
                    'https://images.unsplash.com/photo-1512436991641-6745cdb1723f',
                    'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f',
                    'https://images.unsplash.com/photo-1514996937319-344454492b37',
                ],
                'pants' => [
                    'https://images.unsplash.com/photo-1562157873-818bc0726f5d',
                    'https://images.unsplash.com/photo-1491579143381-7e33c0d91730',
                    'https://images.unsplash.com/photo-1514996937319-344454492b37',
                    'https://images.unsplash.com/photo-1503341455253-b2e723bb3dbb',
                    'https://images.unsplash.com/photo-1507680467858-3f83f0c2d4c9',
                ],
                'bags' => [
                    'https://images.unsplash.com/photo-1517638851339-4aa32003c11a',
                    'https://images.unsplash.com/photo-1523381210434-271e8be1f52b',
                    'https://images.unsplash.com/photo-1519211975560-4ca611f5a72a',
                    'https://images.unsplash.com/photo-1518544801976-3e159e02393c',
                    'https://images.unsplash.com/photo-1542291026-7eec264c27ff',
                ],
                'accessories' => [
                    'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab',
                    'https://images.unsplash.com/photo-1535639818669-9b0f06e8182d',
                    'https://images.unsplash.com/photo-1518552987719-40ed8d3fb7c7',
                    'https://images.unsplash.com/photo-1512427691650-1e5b2a9de0c5',
                    'https://images.unsplash.com/photo-1522312346375-d1a52e2b99b3',
                ],
                'activewear' => [
                    'https://images.unsplash.com/photo-1518640467707-6811f4a6ab73',
                    'https://images.unsplash.com/photo-1533049022226-2adc498789c7',
                    'https://images.unsplash.com/photo-1531094079268-4cbf83f50184',
                    'https://images.unsplash.com/photo-1534367610401-9f75f7b74937',
                    'https://images.unsplash.com/photo-1517832207067-4db24a2ae47c',
                ],
                'jewellery' => [
                    'https://images.unsplash.com/photo-1523292562811-8fa7962a78c8',
                    'https://images.unsplash.com/photo-1522312346375-d1a52e2b99b3',
                    'https://images.unsplash.com/photo-1458530970867-aaa3700c4921',
                    'https://images.unsplash.com/photo-1516632664305-eda5bbfdd279',
                    'https://images.unsplash.com/photo-1524504388940-b1c1722653e1',
                ],
            ];

            $colors = ['Charcoal', 'Sienna', 'Emerald', 'Midnight', 'Ivory', 'Ochre', 'Sage', 'Terracotta', 'Blush'];
            $materials = ['Organic cotton', 'Cashmere blend', 'Merino wool', 'Recycled polyester', 'Handloom silk', 'Vegan leather'];

            $existingProductCount = Product::count();
            $totalProducts = max(0, 100 - $existingProductCount);

            for ($i = 0; $i < $totalProducts; $i++) {
                $categoryKey = $categories->keys()->random();
                $category = $categories[$categoryKey]['model'];
                $definition = $categories[$categoryKey];
                $seller = $sellers->random();

                $categoryImages = $imageLibrary[$categoryKey] ?? collect($imageLibrary)->flatten()->all();
                $primaryImage = Arr::random($categoryImages);

                $title = sprintf(
                    '%s %s',
                    Arr::random($definition['adjectives']),
                    Arr::random($definition['keywords'])
                );

                $product = Product::factory()
                    ->for($seller, 'seller')
                    ->for($category)
                    ->state(function () use ($title, $categoryKey, $materials) {
                        $price = fake()->numberBetween(1800, 26000);

                        return [
                            'title' => $title,
                            'slug' => Str::slug($title . '-' . fake()->unique()->numberBetween(1000, 9999)),
                            'price' => $price,
                            'compare_at_price' => fake()->boolean(45) ? $price + fake()->numberBetween(200, 3200) : null,
                            'metadata' => [
                                'material' => Arr::random($materials),
                                'care' => fake()->randomElement(['Dry clean', 'Gentle machine wash', 'Hand wash cold']),
                                'collection' => Str::title($categoryKey) . ' Capsule',
                            ],
                            'published_at' => now()->subDays(fake()->numberBetween(0, 90)),
                        ];
                    })
                    ->create();

                $images = collect($categoryImages)
                    ->reject(fn ($url) => $url === $primaryImage)
                    ->shuffle()
                    ->take(fake()->numberBetween(2, 4))
                    ->prepend($primaryImage)
                    ->values();

                foreach ($images as $position => $url) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'url' => $url . '?auto=format&fit=crop&w=1200&q=80',
                        'is_primary' => $position === 0,
                        'position' => $position,
                    ]);
                }

                // Variants
                $sizeOptions = $definition['sizes'];
                $variantCount = min(count($sizeOptions), fake()->numberBetween(2, 4));
                $variantSizes = Arr::wrap(Arr::random($sizeOptions, $variantCount));

                foreach ($variantSizes as $size) {
                    ProductVariant::factory()
                        ->for($product)
                        ->state(function () use ($product, $size, $colors) {
                            $basePrice = $product->price;
                            $adjustment = fake()->boolean(30) ? fake()->numberBetween(-200, 600) : 0;

                            return [
                                'size' => $size,
                                'color' => Arr::random($colors),
                                'price' => max(1500, $basePrice + $adjustment),
                                'stock' => fake()->numberBetween(10, 120),
                            ];
                        })
                        ->create();
                }

                // Reviews
                $reviewers = $customers->random(fake()->numberBetween(2, 4));
                foreach ($reviewers as $customer) {
                    ProductReview::factory()
                        ->for($product)
                        ->for($customer, 'user')
                        ->state([
                            'rating' => fake()->numberBetween(3, 5),
                            'title' => Arr::random([
                                'Staple in my wardrobe',
                                'Exceptional quality',
                                'Perfect fit and finish',
                                'Exactly what I needed',
                                'Elevated everyday piece',
                            ]),
                        ])
                        ->create();
                }
            }
        });
    }
}
