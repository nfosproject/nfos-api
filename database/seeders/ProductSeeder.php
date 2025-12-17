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
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/478719/item/usgoods_58_478719_3x4.jpg?width=300',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/478730/item/usgoods_31_478730_3x4.jpg?width=300',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/479616/sub/usgoods_479616_sub7_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480490/item/usgoods_08_480490_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/480490/sub/goods_480490_sub14_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480832/item/usgoods_67_480832_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480832/sub/usgoods_480832_sub7_3x4.jpg'
                ],
                'men' => [
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480832/sub/usgoods_480832_sub7_3x4.jpg'
                ],
                'footwear' => [
                    'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/478322/item/goods_01_478322_3x4.jpg?width=300',
                    'https://cdn.salla.sa/gXmRK/H0t0sJZopuiOsQtqp4euPrdkBZvTg4r21135F720.jpg',
                    'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS-08DCmLf9IaodSnrNQQ_7lgfU2eVmsCCx7jUEKxHi0QNSwqe-_tS1dEJeF2IuVJC_Z-Y&usqp=CAU',
                    'https://thumblr.uniid.it/product/407153/0849c57ccba4.jpg?width=3840&format=webp&q=75',
                    'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSl82wF8z4eOJqtyWsFUD-C43fdF2EJNnMGUZGzyl78jnRxkmz97GW-hVEzZp0q6VJmyOs&usqp=CAU',
                    'https://images.puma.com/image/upload/f_auto,q_auto,w_600,b_rgb:FAFAFA/global/406203/03/sv01/fnd/ZAF/fmt/png'
                ],
                'outerwear' => [
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/478719/item/usgoods_58_478719_3x4.jpg?width=300',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/478730/item/usgoods_31_478730_3x4.jpg?width=300',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/479616/sub/usgoods_479616_sub7_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480490/item/usgoods_08_480490_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/480490/sub/goods_480490_sub14_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480832/item/usgoods_67_480832_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480832/sub/usgoods_480832_sub7_3x4.jpg'
                ],
                'tops' => [
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/478719/item/usgoods_58_478719_3x4.jpg?width=300',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/478730/item/usgoods_31_478730_3x4.jpg?width=300',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/479616/sub/usgoods_479616_sub7_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480490/item/usgoods_08_480490_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/480490/sub/goods_480490_sub14_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480832/item/usgoods_67_480832_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480832/sub/usgoods_480832_sub7_3x4.jpg'
                ],
                'pants' => [
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/478719/item/usgoods_58_478719_3x4.jpg?width=300',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/478730/item/usgoods_31_478730_3x4.jpg?width=300',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/479616/sub/usgoods_479616_sub7_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480490/item/usgoods_08_480490_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/480490/sub/goods_480490_sub14_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480832/item/usgoods_67_480832_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480832/sub/usgoods_480832_sub7_3x4.jpg'
                ],
                'bags' => [
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/478719/item/usgoods_58_478719_3x4.jpg?width=300',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/478730/item/usgoods_31_478730_3x4.jpg?width=300',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/479616/sub/usgoods_479616_sub7_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480490/item/usgoods_08_480490_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/480490/sub/goods_480490_sub14_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480832/item/usgoods_67_480832_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480832/sub/usgoods_480832_sub7_3x4.jpg'
                ],
                'accessories' => [
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/478719/item/usgoods_58_478719_3x4.jpg?width=300',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/478730/item/usgoods_31_478730_3x4.jpg?width=300',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/479616/sub/usgoods_479616_sub7_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480490/item/usgoods_08_480490_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/480490/sub/goods_480490_sub14_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480832/item/usgoods_67_480832_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480832/sub/usgoods_480832_sub7_3x4.jpg'
                ],
                'activewear' => [
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/478719/item/usgoods_58_478719_3x4.jpg?width=300',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/478730/item/usgoods_31_478730_3x4.jpg?width=300',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/479616/sub/usgoods_479616_sub7_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480490/item/usgoods_08_480490_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/480490/sub/goods_480490_sub14_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480832/item/usgoods_67_480832_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480832/sub/usgoods_480832_sub7_3x4.jpg'
                ],
                'jewellery' => [
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/478719/item/usgoods_58_478719_3x4.jpg?width=300',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/478730/item/usgoods_31_478730_3x4.jpg?width=300',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/479616/sub/usgoods_479616_sub7_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480490/item/usgoods_08_480490_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/480490/sub/goods_480490_sub14_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480832/item/usgoods_67_480832_3x4.jpg',
                    'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/480832/sub/usgoods_480832_sub7_3x4.jpg'
                ],
            ];

            $colors = ['Charcoal', 'Sienna', 'Emerald', 'Midnight', 'Ivory', 'Ochre', 'Sage', 'Terracotta', 'Blush'];
            $materials = ['Organic cotton', 'Cashmere blend', 'Merino wool', 'Recycled polyester', 'Handloom silk', 'Vegan leather'];

            $existingProductCount = Product::count();
            $totalProducts = max(0, 100 - $existingProductCount);

            if ($totalProducts === 0) {
                $this->command->info('Products already seeded. Skipping...');
                return;
            }

            $this->command->info("Seeding {$totalProducts} products...");

            // Pre-generate all product data
            $productsData = [];
            $imagesData = [];
            $variantsData = [];
            $reviewsData = [];
            $usedSlugs = Product::pluck('slug')->toArray();
            $usedSkus = Product::pluck('sku')->toArray();
            $usedVariantSkus = ProductVariant::pluck('sku')->toArray();

            $reviewTitles = [
                'Staple in my wardrobe',
                'Exceptional quality',
                'Perfect fit and finish',
                'Exactly what I needed',
                'Elevated everyday piece',
            ];

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

                // Generate unique slug
                $baseSlug = Str::slug($title);
                $slug = $baseSlug;
                $counter = 1;
                while (in_array($slug, $usedSlugs)) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                $usedSlugs[] = $slug;

                        $price = fake()->numberBetween(1800, 26000);
                $productId = (string) Str::uuid();

                // Generate unique SKU
                do {
                    $sku = strtoupper(Str::random(3)) . '-' . fake()->numberBetween(10000, 99999);
                } while (in_array($sku, $usedSkus));
                $usedSkus[] = $sku;

                $productsData[] = [
                    'id' => $productId,
                    'user_id' => $seller->id,
                    'category_id' => $category->id,
                            'title' => $title,
                    'slug' => $slug,
                    'sku' => $sku,
                    'description' => fake()->paragraphs(3, true),
                            'price' => $price,
                            'compare_at_price' => fake()->boolean(45) ? $price + fake()->numberBetween(200, 3200) : null,
                    'stock' => fake()->numberBetween(10, 150),
                    'status' => 'active',
                    'metadata' => json_encode([
                                'material' => Arr::random($materials),
                                'care' => fake()->randomElement(['Dry clean', 'Gentle machine wash', 'Hand wash cold']),
                                'collection' => Str::title($categoryKey) . ' Capsule',
                    ]),
                            'published_at' => now()->subDays(fake()->numberBetween(0, 90)),
                    'created_at' => now(),
                    'updated_at' => now(),
                        ];

                // Prepare images data
                $images = collect($categoryImages)
                    ->reject(fn ($url) => $url === $primaryImage)
                    ->shuffle()
                    ->take(fake()->numberBetween(2, 4))
                    ->prepend($primaryImage)
                    ->values();

                foreach ($images as $position => $url) {
                    $imagesData[] = [
                        'id' => (string) Str::uuid(),
                        'product_id' => $productId,
                        'url' => $url . '?auto=format&fit=crop&w=1200&q=80',
                        'video_url' => $position === 0 ? 'https://videos.pexels.com/video-files/7681932/pexels-photo-7681932-1920x1080-30fps.mp4' : null,
                        'duration' => $position === 0 ? 30 : null,
                        'is_primary' => $position === 0,
                        'position' => $position,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Prepare variants data
                $sizeOptions = $definition['sizes'];
                $variantCount = min(count($sizeOptions), fake()->numberBetween(2, 4));
                $variantSizes = Arr::wrap(Arr::random($sizeOptions, $variantCount));

                foreach ($variantSizes as $size) {
                    $basePrice = $price;
                            $adjustment = fake()->boolean(30) ? fake()->numberBetween(-200, 600) : 0;

                    // Generate unique variant SKU
                    do {
                        $variantSku = strtoupper(Str::random(4)) . fake()->numberBetween(1000, 9999);
                    } while (in_array($variantSku, $usedVariantSkus));
                    $usedVariantSkus[] = $variantSku;

                    $variantsData[] = [
                        'id' => (string) Str::uuid(),
                        'product_id' => $productId,
                        'sku' => $variantSku,
                        'title' => fake()->randomElement(['Standard fit', 'Slim fit', 'Relaxed fit', 'Tall fit']),
                                'size' => $size,
                                'color' => Arr::random($colors),
                                'price' => max(1500, $basePrice + $adjustment),
                                'stock' => fake()->numberBetween(10, 120),
                        'attributes' => json_encode([
                            'material' => Arr::random($materials),
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Prepare reviews data
                $reviewCount = fake()->numberBetween(2, 4);
                $reviewers = $customers->random($reviewCount);

                foreach ($reviewers as $customer) {
                    $reviewsData[] = [
                        'id' => (string) Str::uuid(),
                        'product_id' => $productId,
                        'user_id' => $customer->id,
                            'rating' => fake()->numberBetween(3, 5),
                        'title' => Arr::random($reviewTitles),
                        'body' => fake()->paragraphs(2, true),
                        'attributes' => json_encode([
                            'fit' => fake()->randomElement(['Runs small', 'True to size', 'Runs large']),
                            'quality' => fake()->randomElement(['Excellent', 'Good', 'Premium']),
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Bulk insert products
            $this->command->info('Inserting products...');
            foreach (array_chunk($productsData, 50) as $chunk) {
                Product::insert($chunk);
            }

            // Bulk insert images
            $this->command->info('Inserting product images...');
            foreach (array_chunk($imagesData, 100) as $chunk) {
                ProductImage::insert($chunk);
            }

            // Bulk insert variants
            $this->command->info('Inserting product variants...');
            foreach (array_chunk($variantsData, 100) as $chunk) {
                ProductVariant::insert($chunk);
            }

            // Bulk insert reviews
            $this->command->info('Inserting product reviews...');
            foreach (array_chunk($reviewsData, 100) as $chunk) {
                ProductReview::insert($chunk);
            }

            // Add specific product: Pile Lined Fleece Relaxed Cardigan
            $this->command->info('Adding Pile Lined Fleece Relaxed Cardigan...');
            $this->addSpecificProduct($sellers, $categories, $customers);

            $this->command->info("Successfully seeded {$totalProducts} products with images, variants, and reviews!");
        });
    }

    private function addSpecificProduct($sellers, $categories, $customers): void
    {
        // Check if product already exists
        $existingProduct = Product::where('slug', 'pile-lined-fleece-relaxed-cardigan')->first();
        if ($existingProduct) {
            $this->command->info('Pile Lined Fleece Relaxed Cardigan already exists. Skipping...');
            return;
        }

        $outerwearCategory = $categories['outerwear']['model'] ?? null;
        if (!$outerwearCategory) {
            $this->command->warn('Outerwear category not found. Skipping specific product...');
            return;
        }

        $seller = $sellers->random();
        $productId = (string) Str::uuid();
        $price = 4500; // Set a reasonable price
        $compareAtPrice = 5500; // Original price

        // Generate unique SKU
        do {
            $sku = 'CARD-' . fake()->numberBetween(10000, 99999);
        } while (Product::where('sku', $sku)->exists());

        // Create product
        $product = Product::create([
            'id' => $productId,
            'user_id' => $seller->id,
            'category_id' => $outerwearCategory->id,
            'title' => 'Pile Lined Fleece Relaxed Cardigan',
            'slug' => 'pile-lined-fleece-relaxed-cardigan',
            'sku' => $sku,
            'description' => 'A cozy and comfortable relaxed cardigan featuring a soft pile-lined interior for extra warmth. Perfect for layering during cooler months. Made with quality materials for lasting comfort and style.',
            'price' => $price,
            'compare_at_price' => $compareAtPrice,
            'stock' => fake()->numberBetween(20, 80),
            'status' => 'active',
            'metadata' => json_encode([
                'material' => 'Fleece blend',
                'care' => 'Machine wash cold, tumble dry low',
                'collection' => 'Outerwear Capsule',
            ]),
            'published_at' => now()->subDays(fake()->numberBetween(0, 30)),
        ]);

        // Add product images
        $productImages = [
            [
                'id' => (string) Str::uuid(),
                'product_id' => $productId,
                'url' => 'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/479616/item/usgoods_02_479616_3x4.jpg?width=400',
                'video_url' => 'https://videos.pexels.com/video-files/7681932/pexels-photo-7681932-1920x1080-30fps.mp4',
                'duration' => 30,
                'is_primary' => true,
                'position' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'product_id' => $productId,
                'url' => 'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/479616/sub/usgoods_479616_sub7_3x4.jpg?width=400',
                'video_url' => null,
                'duration' => null,
                'is_primary' => false,
                'position' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        ProductImage::insert($productImages);

        // Add product variants (sizes)
        $sizes = ['S', 'M', 'L', 'XL'];
        $variants = [];
        foreach ($sizes as $index => $size) {
            do {
                $variantSku = $sku . '-' . $size;
            } while (ProductVariant::where('sku', $variantSku)->exists());

            $variants[] = [
                'id' => (string) Str::uuid(),
                'product_id' => $productId,
                'sku' => $variantSku,
                'title' => 'Size ' . $size,
                'size' => $size,
                'price' => $price,
                'stock' => fake()->numberBetween(5, 20),
                'attributes' => json_encode(['size' => $size]),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        ProductVariant::insert($variants);

        // Add some reviews
        $reviewCount = fake()->numberBetween(2, 4);
        $reviewers = $customers->random(min($reviewCount, $customers->count()));
        $reviews = [];

        foreach ($reviewers as $customer) {
            $reviews[] = [
                'id' => (string) Str::uuid(),
                'product_id' => $productId,
                'user_id' => $customer->id,
                'rating' => fake()->numberBetween(4, 5),
                'title' => Arr::random(['Very comfortable', 'Great quality', 'Perfect fit', 'Love this cardigan']),
                'body' => fake()->paragraphs(2, true),
                'attributes' => json_encode([
                    'fit' => fake()->randomElement(['Runs small', 'True to size', 'Runs large']),
                    'quality' => 'Excellent',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($reviews)) {
            ProductReview::insert($reviews);
        }

        $this->command->info('Successfully added Pile Lined Fleece Relaxed Cardigan!');
    }
}
