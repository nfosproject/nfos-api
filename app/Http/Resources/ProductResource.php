<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => (int) $this->price,
            'compare_at_price' => $this->compare_at_price ? (int) $this->compare_at_price : null,
            'stock' => (int) $this->stock,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'published_at' => optional($this->published_at)->toIso8601String(),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'seller' => $this->whenLoaded('seller', fn () => [
                'id' => $this->seller->id,
                'name' => $this->seller->name,
            ]),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => [
                'url' => $image->url,
                'is_primary' => (bool) $image->is_primary,
                'position' => (int) $image->position,
            ])),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants->map(fn ($variant) => [
                'size' => $variant->size,
                'color' => $variant->color,
                'price' => (int) $variant->price,
                'stock' => (int) $variant->stock,
                'attributes' => $variant->attributes,
            ])),
            'reviews' => $this->whenLoaded('reviews', fn () => $this->reviews->map(fn ($review) => [
                'id' => $review->id,
                'rating' => (int) $review->rating,
                'title' => $review->title,
                'body' => $review->body,
                'attributes' => $review->attributes,
                'user' => $review->relationLoaded('user') ? [
                    'id' => $review->user->id,
                    'name' => $review->user->name,
                ] : null,
                'created_at' => $review->created_at?->toIso8601String(),
            ])),
        ];
    }
}
