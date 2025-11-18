<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'display_order' => (int) $this->display_order,
            'parent_id' => $this->parent_id,
            'products_count' => $this->when(isset($this->products_count), (int) $this->products_count),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
