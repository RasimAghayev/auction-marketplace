<?php

namespace App\Http\Requests\Api\V1;

use App\Modules\Categories\Repositories\CategoryRepository;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function afterValidation(): array
    {
        return [
            function (Validator $validator) {
                $repository = app(CategoryRepository::class);
                $category = $this->route('category');

                $slug = $this->input('slug');
                if ($slug && $slug !== $category->slug && $repository->findBySlug($slug)) {
                    $validator->errors()->add('slug', 'The slug has already been taken.');
                }

                $parentId = $this->input('parent_id');
                if ($parentId && ! $repository->findById($parentId)) {
                    $validator->errors()->add('parent_id', 'The selected parent id is invalid.');
                }
            },
        ];
    }
}
