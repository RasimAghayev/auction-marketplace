<?php

namespace App\Http\Requests\Api\V1;

use App\Modules\Products\Repositories\ProductRepository;
use Illuminate\Foundation\Http\FormRequest;

class StoreAuctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $productId = $this->input('product_id');

        if (! $productId) {
            return true;
        }

        $product = app(ProductRepository::class)->findById($productId);

        if (! $product) {
            return true;
        }

        return $product->seller_id === $this->user()?->id;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required'],
            'starting_price' => ['required', 'numeric', 'min:0.01'],
            'min_bid_increment' => ['required', 'numeric', 'min:0.01'],
            'reserve_price' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['required', 'date', 'after:now'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }
}
