<?php

namespace App\Http\Requests\V1\Auth;

use App\Http\Responses\V1\ApiResponse;
use App\Models\V1\User;
use App\Rules\V1\Auth\NotInHistoryPassword;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
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
        try {
            $user = User::where('email', $this->email)
                ->where('registration_method', 'local')
                ->firstOrFail()
                ->makeVisible('password'); // Esto lanzará ModelNotFoundException automáticamente
        } catch (ModelNotFoundException $e) {
            throw new HttpResponseException(
                ApiResponse::error(
                    'Usuario no encontrado',
                    404,
                    ['email' => ['El correo no está registrado en nuestro sistema']]
                )
            );
        }
        return [
            'token' => 'required',
            'email' => 'required|string|email|max:255',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->max(15)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
                new NotInHistoryPassword($user),
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
