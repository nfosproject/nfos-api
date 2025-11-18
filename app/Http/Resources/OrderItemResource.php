<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $snapshot = $this->snapshot ?? [];
        
        // Always try to get title from product relationship if available, even if snapshot exists
        // This ensures we have the most up-to-date product title
        if ($this->relationLoaded('product') && $this->product) {
            // Use product title if snapshot name/title is missing or empty
            if (empty($snapshot['name']) || !isset($snapshot['name']) || $snapshot['name'] === 'Product') {
                $snapshot['name'] = $this->product->title;
            }
            if (empty($snapshot['title']) || !isset($snapshot['title'])) {
                $snapshot['title'] = $this->product->title;
            }
            
            // Use seller name if snapshot vendor is missing or empty
            if (empty($snapshot['vendor']) && $this->product->relationLoaded('seller') && $this->product->seller) {
                $snapshot['vendor'] = $this->product->seller->name;
            }
            
            // Add product image if snapshot doesn't have one
            if (empty($snapshot['image']) && $this->product->relationLoaded('images')) {
                $primaryImage = $this->product->images->firstWhere('is_primary', true);
                if (!$primaryImage) {
                    $primaryImage = $this->product->images->first();
                }
                if ($primaryImage) {
                    $snapshot['image'] = $primaryImage->url;
                }
            }
        }
        
        // If we still don't have a name/title after checking product, try to load it
        if (empty($snapshot['name']) || !isset($snapshot['name'])) {
            if ($this->product_id && !$this->relationLoaded('product')) {
                // Try to load product if not already loaded
                $product = \App\Models\Product::find($this->product_id);
                if ($product) {
                    $snapshot['name'] = $product->title;
                    $snapshot['title'] = $product->title;
                }
            }
            
            // Last resort: use 'Product' as fallback
            if (empty($snapshot['name']) || !isset($snapshot['name'])) {
                $snapshot['name'] = 'Product';
                $snapshot['title'] = 'Product';
            }
        }

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'quantity' => (int) $this->quantity,
            'unit_price' => (int) $this->unit_price,
            'line_total' => (int) $this->line_total,
            'snapshot' => $snapshot,
        ];
    }
}
