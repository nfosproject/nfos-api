<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->with([
                'category:id,name,slug',
                'seller:id,name',
                'images:product_id,url,is_primary,position',
                'variants:product_id,size,color,price,stock',
            ])
            ->where('status', 'active');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($slug = $request->string('category')->toString()) {
            $category = Category::where('slug', $slug)->first();
            if ($category) {
                $categoryIds = [$category->id];
                if ($category->children()->exists()) {
                    $categoryIds = array_merge($categoryIds, $category->children()->pluck('id')->all());
                }
                $query->whereIn('category_id', $categoryIds);
            }
        }

        if ($minPrice = $request->integer('min_price')) {
            $query->where('price', '>=', $minPrice);
        }

        if ($maxPrice = $request->integer('max_price')) {
            $query->where('price', '<=', $maxPrice);
        }

        if ($request->boolean('featured')) {
            $query->orderByDesc('published_at');
        } elseif ($request->boolean('random')) {
            $query->inRandomOrder();
        } else {
            $query->orderByDesc('created_at');
        }

        $perPage = (int) $request->integer('per_page', 20);
        $products = $query->paginate(min(max($perPage, 6), 60))->withQueryString();

        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        $product->loadMissing([
            'category:id,name,slug',
            'seller:id,name',
            'images:product_id,url,is_primary,position',
            'variants:product_id,size,color,price,stock,attributes',
            'reviews' => fn ($query) => $query->with('user:id,name'),
        ]);

        return new ProductResource($product);
    }
}
