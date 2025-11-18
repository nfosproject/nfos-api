<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 12);
        $perPage = max(1, min($perPage, 100));

        $query = Product::query()
            ->with([
                'seller:id,name,email',
                'category:id,name',
                'images:product_id,url,is_primary,position',
            ]);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($sellerId = $request->string('seller_id')->toString()) {
            $query->where('user_id', $sellerId);
        }

        if ($categoryId = $request->string('category_id')->toString()) {
            $query->where('category_id', $categoryId);
        }

        $products = $query
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $items = $products->getCollection()
            ->map(fn (Product $product) => $this->formatProduct($product))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'pagination' => [
                    'currentPage' => $products->currentPage(),
                    'perPage' => $products->perPage(),
                    'total' => $products->total(),
                    'lastPage' => $products->lastPage(),
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);

        $product = Product::create($data);
        $product->load([
            'seller:id,name,email',
            'category:id,name',
            'images:product_id,url,is_primary,position',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatProduct($product),
        ], 201);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request, $product);

        $product->update($data);
        $product->refresh()->load([
            'seller:id,name,email',
            'category:id,name',
            'images:product_id,url,is_primary,position',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatProduct($product),
        ]);
    }

    public function destroy(Product $product)
    {
        DB::transaction(function () use ($product) {
            $product->images()->delete();
            $product->variants()->delete();
            $product->orderItems()->delete();
            $product->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }

    protected function validateProduct(Request $request, ?Product $product = null): array
    {
        $id = $product?->id;

        $rules = [
            'title' => [$product ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => [$product ? 'sometimes' : 'required', 'integer', 'min:0'],
            'compare_at_price' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'stock' => [$product ? 'sometimes' : 'required', 'integer', 'min:0'],
            'status' => [$product ? 'sometimes' : 'required', Rule::in(['draft', 'active', 'archived'])],
            'seller_id' => [$product ? 'sometimes' : 'required', 'exists:users,id'],
            'category_id' => [$product ? 'sometimes' : 'required', 'exists:categories,id'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($id)],
            'sku' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($id)],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'published_at' => ['sometimes', 'nullable', 'date'],
        ];

        $validated = $request->validate($rules);

        $data = [];
        $data['title'] = $validated['title'] ?? $product?->title;
        $data['description'] = $validated['description'] ?? $product?->description;
        $data['price'] = isset($validated['price']) ? (int) $validated['price'] : (int) ($product?->price ?? 0);
        $data['compare_at_price'] = array_key_exists('compare_at_price', $validated)
            ? ($validated['compare_at_price'] !== null ? (int) $validated['compare_at_price'] : null)
            : $product?->compare_at_price;
        $data['stock'] = isset($validated['stock']) ? (int) $validated['stock'] : (int) ($product?->stock ?? 0);
        $data['status'] = $validated['status'] ?? $product?->status ?? 'draft';
        $data['metadata'] = $validated['metadata'] ?? $product?->metadata;
        $data['published_at'] = $validated['published_at'] ?? $product?->published_at;

        $sellerId = $validated['seller_id'] ?? $product?->user_id;
        $categoryId = $validated['category_id'] ?? $product?->category_id;

        $data['user_id'] = $sellerId;
        $data['category_id'] = $categoryId;

        $title = $data['title'] ?? 'product';

        if (array_key_exists('slug', $validated)) {
            $data['slug'] = $validated['slug'] ?: $this->makeUniqueSlug($title, $id);
        } else {
            $data['slug'] = $product?->slug ?: $this->makeUniqueSlug($title, $id);
        }

        if (array_key_exists('sku', $validated)) {
            $data['sku'] = $validated['sku'] ?: $this->makeUniqueSku($id);
        } else {
            $data['sku'] = $product?->sku ?: $this->makeUniqueSku($id);
        }

        return $data;
    }

    protected function makeUniqueSlug(string $title, ?string $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base ?: Str::random(8);
        $counter = 1;

        while (Product::where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base ? "{$base}-{$counter}" : Str::random(8);
            $counter++;
        }

        return $slug;
    }

    protected function makeUniqueSku(?string $ignoreId = null): string
    {
        do {
            $sku = strtoupper(Str::random(8));
        } while (Product::where('sku', $sku)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists());

        return $sku;
    }

    protected function formatProduct(Product $product): array
    {
        $primaryImage = $product->images
            ? $product->images->sortBy(fn ($image) => $image->is_primary ? 0 : ($image->position ?? 999))->first()
            : null;

        $price = (int) $product->price;
        $compareAt = $product->compare_at_price ? (int) $product->compare_at_price : null;
        $discountPercent = null;

        if ($compareAt && $compareAt > 0 && $compareAt > $price) {
            $discountPercent = round((($compareAt - $price) / $compareAt) * 100);
        }

        $metadata = $product->metadata ?? [];

        return [
            'id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'description' => $product->description,
            'price' => $price,
            'compare_at_price' => $compareAt,
            'discount_percent' => $discountPercent,
            'seller' => [
                'id' => $product->seller?->id,
                'name' => $product->seller?->name ?? 'Unknown seller',
                'email' => $product->seller?->email,
            ],
            'category' => [
                'id' => $product->category?->id,
                'name' => $product->category?->name ?? 'Uncategorised',
            ],
            'images' => $product->images
                ? $product->images->map(static fn ($image) => $image->url)->filter()->values()
                : [],
            'primary_image' => $primaryImage?->url,
            'rating' => $metadata['average_rating'] ?? null,
            'stock' => (int) $product->stock,
            'status' => $product->status,
            'sales' => $metadata['units_sold'] ?? 0,
            'created_at' => optional($product->created_at)?->toIso8601String(),
            'updated_at' => optional($product->updated_at)?->toIso8601String(),
        ];
    }
}

