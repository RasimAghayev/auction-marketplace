<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'starting_price' => ['sometimes', 'numeric', 'min:0.01'],
            'min_bid_increment' => ['sometimes', 'numeric', 'min:0.01'],
            'reserve_price' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after:start_date'],
        ];
    }
}
