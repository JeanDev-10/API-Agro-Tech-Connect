<?php

namespace App\Http\Requests\V1\Auth;

use App\Http\Responses\V1\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
class RegisterAuthRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:20',
            'lastname' => 'required|string|min:3|max:20',
            'username' => 'required|string|min:3|max:10|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->max(15)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
        ];
    }
     protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();

        throw new HttpResponseException(
            ApiResponse::error('Error de validación',422,$errors)
        );
    } 
}
