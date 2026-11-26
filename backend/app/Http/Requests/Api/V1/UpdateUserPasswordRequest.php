<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'current_password' => ['required', function ($attribute, $value, $fail) use ($user) {
                if (! \Hash::check($value, $user->password)) {
                    $fail('The current password is incorrect.');
                }
            }],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
