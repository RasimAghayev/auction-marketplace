<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'starting_price' => ['sometimes', 'numeric', 'min:0'],
            'condition' => ['sometimes', 'in:new,used,refurbished'],
            'status' => ['sometimes', 'in:draft,published,in_auction,sold'],
        ];
    }
}
