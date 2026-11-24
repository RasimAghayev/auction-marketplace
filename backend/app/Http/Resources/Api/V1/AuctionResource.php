<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuctionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'product_id' => (string) $this->product_id,
            'seller_id' => (string) $this->seller_id,
            'starting_price' => (float) $this->starting_price,
            'current_price' => (float) $this->current_price,
            'min_bid_increment' => (float) $this->min_bid_increment,
            'reserve_price' => $this->reserve_price ? (float) $this->reserve_price : null,
            'start_date' => $this->start_date?->toISOString(),
            'end_date' => $this->end_date?->toISOString(),
            'status' => $this->status->value,
            'winner_id' => $this->winner_id ? (string) $this->winner_id : null,
            'total_bids' => (int) $this->total_bids,
            'version' => (int) $this->version,
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            
            // Computed fields
            'remaining_time' => $this->whenLoaded('remaining_time'),
            'next_minimum_bid' => $this->whenLoaded('next_minimum_bid'),
            
            // Relations
            'product' => new ProductResource($this->whenLoaded('product')),
            'seller' => new UserResource($this->whenLoaded('seller')),
            'winner' => new UserResource($this->whenLoaded('winner')),
            'bids' => BidResource::collection($this->whenLoaded('bids')),
        ];
    }
}