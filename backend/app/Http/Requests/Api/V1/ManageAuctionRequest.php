<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ManageAuctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:cancel,extend'],
            'end_date' => ['sometimes', 'date', 'after:now'],
        ];
    }
}
