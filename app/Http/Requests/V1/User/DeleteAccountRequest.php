<?php

namespace App\Http\Requests\V1\User;

use App\Http\Responses\V1\ApiResponse;
use App\Models\V1\User;
use App\Rules\V1\Auth\NotInHistoryPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

class DeleteAccountRequest extends FormRequest
{
    protected User $user;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Obtener el usuario autenticado desde el token
        $this->user = Auth::user()->makeVisible('password');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->max(15)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
                function ($attribute, $value, $fail) {
                    if (!Hash::check($value, $this->user->password)) {
                        $fail('La contraseña actual no es correcta.');
                    }
                }
            ],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();

        throw new HttpResponseException(
            ApiResponse::error('Error de validación', 422, $errors)
        );
    }
}
