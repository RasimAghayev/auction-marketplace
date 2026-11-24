<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BidResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'auction_id' => (string) $this->auction_id,
            'user_id' => (string) $this->user_id,
            'amount' => (float) $this->amount,
            'status' => $this->status->value,
            'request_id' => $this->request_id,
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relations
            'user' => new UserResource($this->whenLoaded('user')),
            'auction' => new AuctionResource($this->whenLoaded('auction')),
        ];
    }
}