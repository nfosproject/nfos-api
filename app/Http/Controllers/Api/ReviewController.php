<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    /**
     * Get all reviews for a product or all reviews by the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = ProductReview::query()->with(['product:id,title,slug', 'user:id,name']);

        // Filter by product if provided
        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        // Filter by user (default to authenticated user's reviews)
        if ($request->boolean('my_reviews', true)) {
            $query->where('user_id', $user->id);
        } elseif ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        // Filter by rating
        if ($rating = $request->integer('rating')) {
            $query->where('rating', $rating);
        }

        // Sort by newest first
        $query->orderByDesc('created_at');

        $perPage = (int) $request->integer('per_page', 20);
        $reviews = $query->paginate(min(max($perPage, 6), 60))->withQueryString();

        return response()->json([
            'success' => true,
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * Get a specific review
     */
    public function show(Request $request, ProductReview $review)
    {
        $user = $request->user();

        // Users can only view their own reviews unless it's a public product review
        if ($review->user_id !== $user->id) {
            // Allow viewing if it's for a product (public review)
            $review->loadMissing(['product:id,title,slug', 'user:id,name']);
        } else {
            $review->loadMissing(['product:id,title,slug', 'user:id,name']);
        }

        return response()->json([
            'success' => true,
            'data' => new ReviewResource($review),
        ]);
    }

    /**
     * Create a new review
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'order_id' => ['nullable', 'uuid', 'exists:orders,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'attributes' => ['nullable', 'array'],
        ]);

        // Verify product exists
        $product = Product::findOrFail($validated['product_id']);

        // If order_id is provided, verify the order belongs to the user and contains the product
        if (isset($validated['order_id'])) {
            $order = Order::where('id', $validated['order_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Verify the order contains this product
            $orderItem = OrderItem::where('order_id', $order->id)
                ->where('product_id', $validated['product_id'])
                ->first();

            if (!$orderItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'The specified order does not contain this product.',
                ], 422);
            }
        } else {
            // Optional: Verify user has purchased this product (if you want to enforce purchase requirement)
            // For now, we'll allow reviews without order_id
        }

        // Check if user has already reviewed this product
        $existingReview = ProductReview::where('user_id', $user->id)
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this product. You can update your existing review instead.',
            ], 422);
        }

        $review = ProductReview::create([
            'product_id' => $validated['product_id'],
            'user_id' => $user->id,
            'order_id' => $validated['order_id'] ?? null,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'] ?? null,
            'attributes' => $validated['attributes'] ?? null,
        ]);

        $review->loadMissing(['product:id,title,slug', 'user:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data' => new ReviewResource($review),
        ], 201);
    }

    /**
     * Update a review
     */
    public function update(Request $request, ProductReview $review)
    {
        $user = $request->user();

        // Users can only update their own reviews
        if ($review->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found.',
            ], 404);
        }

        $validated = $request->validate([
            'rating' => ['sometimes', 'required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'attributes' => ['nullable', 'array'],
        ]);

        $review->update($validated);
        $review->refresh();
        $review->loadMissing(['product:id,title,slug', 'user:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully.',
            'data' => new ReviewResource($review),
        ]);
    }

    /**
     * Delete a review
     */
    public function destroy(Request $request, ProductReview $review)
    {
        $user = $request->user();

        // Users can only delete their own reviews
        if ($review->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found.',
            ], 404);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully.',
        ]);
    }

    /**
     * Get reviews for a specific product (public endpoint)
     */
    public function productReviews(Request $request, Product $product)
    {
        $query = $product->reviews()->with('user:id,name');

        // Filter by rating
        if ($rating = $request->integer('rating')) {
            $query->where('rating', $rating);
        }

        // Sort options
        $sortBy = $request->string('sort_by', 'newest')->toString();
        switch ($sortBy) {
            case 'oldest':
                $query->orderBy('created_at');
                break;
            case 'highest_rating':
                $query->orderByDesc('rating')->orderByDesc('created_at');
                break;
            case 'lowest_rating':
                $query->orderBy('rating')->orderByDesc('created_at');
                break;
            default: // 'newest'
                $query->orderByDesc('created_at');
        }

        $perPage = (int) $request->integer('per_page', 20);
        $reviews = $query->paginate(min(max($perPage, 6), 60))->withQueryString();

        // Calculate average rating
        $averageRating = $product->reviews()->avg('rating');
        $ratingCount = $product->reviews()->count();
        $ratingDistribution = $product->reviews()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderByDesc('rating')
            ->pluck('count', 'rating')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'average_rating' => round((float) $averageRating, 2),
                'rating_count' => $ratingCount,
                'rating_distribution' => $ratingDistribution,
            ],
        ]);
    }
}

