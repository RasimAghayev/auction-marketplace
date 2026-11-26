<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ModerateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approve,reject'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
