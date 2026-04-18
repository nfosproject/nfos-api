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
                'images:product_id,url,video_url,duration,is_primary,position',
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
            } else {
                // If category not found by slug, try partial matching on category name
                $query->whereHas('category', function ($q) use ($slug) {
                    $slugParts = explode('-', $slug);
                    foreach ($slugParts as $part) {
                        $q->where(function ($subQ) use ($part) {
                            $subQ->where('slug', 'like', "%{$part}%")
                                ->orWhere('name', 'like', "%{$part}%");
                        });
                    }
                });
            }
        }

        // Filter by collection
        if ($collection = $request->string('collection')->toString()) {
            $query->where(function ($q) use ($collection) {
                $q->where('collection', $collection)
                    ->orWhereJsonContains('tags', $collection);
            });
        }

        // Filter by gender
        if ($gender = $request->string('gender')->toString()) {
            $query->where(function ($q) use ($gender) {
                $q->where('gender', $gender)
                    ->orWhere('gender', 'unisex')
                    ->orWhereHas('category', function ($categoryQuery) use ($gender) {
                        $categoryQuery->where('slug', 'like', "%{$gender}%")
                            ->orWhere('name', 'like', "%{$gender}%");
                    });
            });
        }

        if ($minPrice = $request->integer('min_price')) {
            $query->where('price', '>=', $minPrice);
        }

        if ($maxPrice = $request->integer('max_price')) {
            $query->where('price', '<=', $maxPrice);
        }

        // Filter by color (from variants)
        if ($colors = $request->input('colors')) {
            $colorArray = is_array($colors) ? $colors : explode(',', $colors);
            $query->whereHas('variants', function ($q) use ($colorArray) {
                $q->whereIn('color', $colorArray);
            });
        }

        // Filter by size (from variants)
        if ($sizes = $request->input('sizes')) {
            $sizeArray = is_array($sizes) ? $sizes : explode(',', $sizes);
            $query->whereHas('variants', function ($q) use ($sizeArray) {
                $q->whereIn('size', $sizeArray);
            });
        }

        // Filter by discount percentage
        if ($minDiscount = $request->integer('min_discount')) {
            $query->whereRaw('((compare_at_price - price) / NULLIF(compare_at_price, 0) * 100) >= ?', [$minDiscount])
                ->whereNotNull('compare_at_price')
                ->where('compare_at_price', '>', 0);
        }

        // Filter on sale only (products with discount)
        if ($request->boolean('on_sale')) {
            $query->whereNotNull('compare_at_price')
                ->where('compare_at_price', '>', 0)
                ->whereColumn('price', '<', 'compare_at_price');
        }

        // Filter new arrivals (products created in last 30 days)
        if ($request->boolean('new_arrival')) {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        // Global Filters - Brand
        if ($brands = $request->input('brands')) {
            $brandArray = is_array($brands) ? $brands : explode(',', $brands);
            $query->where(function ($q) use ($brandArray) {
                foreach ($brandArray as $brand) {
                    $brandLower = strtolower($brand);
                    if ($brandLower === 'nepali brands' || $brandLower === 'nepali') {
                        $q->orWhereJsonContains('tags', 'nepali-brand')
                            ->orWhereJsonContains('tags', 'made-in-nepal')
                            ->orWhereJsonContains('tags', 'local-designer');
                    } elseif ($brandLower === 'local designers' || $brandLower === 'local') {
                        $q->orWhereJsonContains('tags', 'local-designer')
                            ->orWhereJsonContains('tags', 'nepali-designer');
                    } else {
                        $q->orWhereJsonContains('tags', $brand);
                    }
                }
            });
        }

        // Filter by fit (Regular Fit, Slim Fit, Oversized)
        if ($fits = $request->input('fits')) {
            $fitArray = is_array($fits) ? $fits : explode(',', $fits);
            $query->where(function ($q) use ($fitArray) {
                foreach ($fitArray as $fit) {
                    $q->orWhereJsonContains('tags', strtolower(str_replace(' ', '-', $fit)))
                        ->orWhereJsonContains('metadata->fit', $fit);
                }
            });
        }

        // Filter by fabric
        if ($fabrics = $request->input('fabrics')) {
            $fabricArray = is_array($fabrics) ? $fabrics : explode(',', $fabrics);
            $query->where(function ($q) use ($fabricArray) {
                foreach ($fabricArray as $fabric) {
                    $q->orWhereJsonContains('tags', strtolower($fabric))
                        ->orWhereJsonContains('metadata->fabric', $fabric);
                }
            });
        }

        // Filter by style
        if ($styles = $request->input('styles')) {
            $styleArray = is_array($styles) ? $styles : explode(',', $styles);
            $query->where(function ($q) use ($styleArray) {
                foreach ($styleArray as $style) {
                    $q->orWhereJsonContains('tags', strtolower($style))
                        ->orWhereJsonContains('metadata->style', $style);
                }
            });
        }

        // Filter by occasion
        if ($occasions = $request->input('occasions')) {
            $occasionArray = is_array($occasions) ? $occasions : explode(',', $occasions);
            $query->where(function ($q) use ($occasionArray) {
                foreach ($occasionArray as $occasion) {
                    $q->orWhereJsonContains('tags', strtolower($occasion))
                        ->orWhereJsonContains('metadata->occasion', $occasion);
                }
            });
        }

        // Filter by season
        if ($seasons = $request->input('seasons')) {
            $seasonArray = is_array($seasons) ? $seasons : explode(',', $seasons);
            $query->where(function ($q) use ($seasonArray) {
                foreach ($seasonArray as $season) {
                    $q->orWhereJsonContains('tags', strtolower($season))
                        ->orWhereJsonContains('metadata->season', $season);
                }
            });
        }

        // Filter by length (for women's clothing)
        if ($lengths = $request->input('lengths')) {
            $lengthArray = is_array($lengths) ? $lengths : explode(',', $lengths);
            $query->where(function ($q) use ($lengthArray) {
                foreach ($lengthArray as $length) {
                    $q->orWhereJsonContains('tags', strtolower($length))
                        ->orWhereJsonContains('metadata->length', $length);
                }
            });
        }

        // Filter by type (for footwear)
        if ($types = $request->input('types')) {
            $typeArray = is_array($types) ? $types : explode(',', $types);
            $query->where(function ($q) use ($typeArray) {
                foreach ($typeArray as $type) {
                    $q->orWhereJsonContains('tags', strtolower($type))
                        ->orWhereJsonContains('metadata->type', $type);
                }
            });
        }

        // Filter by material
        if ($materials = $request->input('materials')) {
            $materialArray = is_array($materials) ? $materials : explode(',', $materials);
            $query->where(function ($q) use ($materialArray) {
                foreach ($materialArray as $material) {
                    $q->orWhereJsonContains('tags', strtolower($material))
                        ->orWhereJsonContains('metadata->material', $material);
                }
            });
        }

        // Filter by activity (for activewear)
        if ($activities = $request->input('activities')) {
            $activityArray = is_array($activities) ? $activities : explode(',', $activities);
            $query->where(function ($q) use ($activityArray) {
                foreach ($activityArray as $activity) {
                    $q->orWhereJsonContains('tags', strtolower($activity))
                        ->orWhereJsonContains('metadata->activity', $activity);
                }
            });
        }

        // Filter by product type (for Nepali Edit)
        if ($productTypes = $request->input('product_types')) {
            $productTypeArray = is_array($productTypes) ? $productTypes : explode(',', $productTypes);
            $query->where(function ($q) use ($productTypeArray) {
                foreach ($productTypeArray as $productType) {
                    $q->orWhereJsonContains('tags', strtolower(str_replace(' ', '-', $productType)))
                        ->orWhereJsonContains('metadata->product_type', $productType);
                }
            });
        }

        // Filter by festival
        if ($festivals = $request->input('festivals')) {
            $festivalArray = is_array($festivals) ? $festivals : explode(',', $festivals);
            $query->where(function ($q) use ($festivalArray) {
                foreach ($festivalArray as $festival) {
                    $q->orWhereJsonContains('tags', strtolower($festival))
                        ->orWhereJsonContains('metadata->festival', $festival);
                }
            });
        }

        // Filter by craft
        if ($crafts = $request->input('crafts')) {
            $craftArray = is_array($crafts) ? $crafts : explode(',', $crafts);
            $query->where(function ($q) use ($craftArray) {
                foreach ($craftArray as $craft) {
                    $q->orWhereJsonContains('tags', strtolower($craft))
                        ->orWhereJsonContains('metadata->craft', $craft);
                }
            });
        }

        // Filter by availability - In Stock
        if ($request->boolean('in_stock')) {
            $query->where('stock', '>', 0)
                ->orWhereHas('variants', function ($q) {
                    $q->where('stock', '>', 0);
                });
        }

        // Filter by availability - Fast Delivery (products with metadata fast_delivery or tag)
        if ($request->boolean('fast_delivery')) {
            $query->where(function ($q) {
                $q->whereJsonContains('tags', 'fast-delivery')
                    ->orWhereJsonContains('metadata->fast_delivery', true);
            });
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
            'images:product_id,url,video_url,duration,is_primary,position',
            'variants:product_id,size,color,price,stock,attributes',
            'reviews' => fn ($query) => $query->with('user:id,name'),
        ]);

        return new ProductResource($product);
    }
}
