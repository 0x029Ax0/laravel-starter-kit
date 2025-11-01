<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore(auth()->id()),
            ],
            'avatar' => 'sometimes|image|mimes:jpeg,png,webp|max:10240|dimensions:min_width=300,min_height=300',
        ];
    }
}
