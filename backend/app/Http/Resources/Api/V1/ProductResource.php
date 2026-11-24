<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'seller_id' => (string) $this->seller_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'category_id' => (string) $this->category_id,
            'images' => $this->images,
            'condition' => $this->condition->value,
            'starting_price' => (float) $this->starting_price,
            'reserve_price' => $this->reserve_price ? (float) $this->reserve_price : null,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Relations
            'seller' => new UserResource($this->whenLoaded('seller')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'auction' => new AuctionResource($this->whenLoaded('auction')),
        ];
    }
}