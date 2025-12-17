<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoryTreeSeeder extends Seeder
{
    public function run(): void
    {
        // Women root
        $women = Category::firstOrCreate(
            ['slug' => 'women'],
            [
                'name' => 'Women',
                'display_order' => 1,
                'image_url' => 'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/454939/item/goods_00_454939_3x4.jpg',
            ],
        );

        $this->createChildren($women, [
            [
                'name' => 'Tops',
                'slug' => 'women-tops',
                'image' => 'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/461756/item/goods_00_461756_3x4.jpg',
            ],
            [
                'name' => 'Bottoms',
                'slug' => 'women-bottoms',
                'image' => 'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/461408/item/goods_09_461408_3x4.jpg',
            ],
            [
                'name' => 'Dresses & Ethnic',
                'slug' => 'women-dresses-ethnic',
                'image' => 'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/455648/item/goods_69_455648_3x4.jpg',
            ],
            [
                'name' => 'Outerwear',
                'slug' => 'women-outerwear',
                'image' => 'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/456844/item/goods_69_456844_3x4.jpg',
            ],
            [
                'name' => 'Footwear',
                'slug' => 'women-footwear',
                'image' => 'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/455705/item/goods_09_455705_3x4.jpg',
            ],
            [
                'name' => 'Accessories',
                'slug' => 'women-accessories',
                'image' => 'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/457152/item/goods_09_457152_3x4.jpg',
            ],
        ]);

        // Men root
        $men = Category::firstOrCreate(
            ['slug' => 'men'],
            [
                'name' => 'Men',
                'display_order' => 2,
                'image_url' => 'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/457374/item/goods_69_457374_3x4.jpg',
            ],
        );

        $this->createChildren($men, [
            [
                'name' => 'T-shirts & Polos',
                'slug' => 'men-tshirts-polos',
                'image' => 'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/461928/item/goods_69_461928_3x4.jpg',
            ],
            [
                'name' => 'Shirts',
                'slug' => 'men-shirts',
                'image' => 'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/456370/item/goods_01_456370_3x4.jpg',
            ],
            [
                'name' => 'Bottoms',
                'slug' => 'men-bottoms',
                'image' => 'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/457129/item/goods_69_457129_3x4.jpg',
            ],
            [
                'name' => 'Outerwear',
                'slug' => 'men-outerwear',
                'image' => 'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/456414/item/goods_69_456414_3x4.jpg',
            ],
            [
                'name' => 'Ethnic',
                'slug' => 'men-ethnic',
                'image' => 'https://image.uniqlo.com/UQ/ST3/us/imagesgoods/479616/sub/usgoods_479616_sub7_3x4.jpg',
            ],
            [
                'name' => 'Footwear',
                'slug' => 'men-footwear',
                'image' => 'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/478322/item/goods_01_478322_3x4.jpg',
            ],
            [
                'name' => 'Accessories',
                'slug' => 'men-accessories',
                'image' => 'https://image.uniqlo.com/UQ/ST3/WesternCommon/imagesgoods/457152/item/goods_03_457152_3x4.jpg',
            ],
        ]);
    }

    private function createChildren(Category $parent, array $children): void
    {
        foreach ($children as $index => $child) {
            Category::updateOrCreate(
                ['slug' => $child['slug']],
                [
                    'name' => $child['name'],
                    'parent_id' => $parent->id,
                    'display_order' => $index,
                    'image_url' => $child['image'],
                ],
            );
        }
    }
}


