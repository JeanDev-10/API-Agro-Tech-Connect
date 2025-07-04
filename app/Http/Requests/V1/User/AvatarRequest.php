<?php

namespace App\Http\Requests\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class AvatarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'max:3072',
                File::image()
                    ->types(['jpg', 'jpeg', 'png'])
            ]
        ];
    }
    public function messages(): array
{
    return [
        'avatar.max' => 'La imagen no debe superar los 3MB.',
    ];
}
}
