<?php

namespace App\Http\Requests\Api\V1;

use App\Modules\Products\Services\ImageUploadService;
use Illuminate\Foundation\Http\FormRequest;

class UploadProductImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product !== null && $this->user()?->can('update', $product);
    }

    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'max:'.ImageUploadService::MAX_FILES],
            'images.*' => ['image', 'max:'.ImageUploadService::MAX_FILE_SIZE_KB],
        ];
    }
}
