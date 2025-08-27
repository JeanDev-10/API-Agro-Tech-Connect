<?php

namespace App\Http\Requests\V1\User;

use App\Http\Responses\V1\ApiResponse;
use App\Models\V1\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
class FollowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    try {
                        $decrypted = Crypt::decrypt($value);
                        if (!is_numeric($decrypted)) {
                            $fail('El ID de usuario no es válido.');
                        }
                    } catch (\Exception $e) {
                        $fail('El ID de usuario no está cifrado correctamente.');
                    }
                }
            ]
        ];
    }

    public function getDecryptedUserId(): int
    {
        return (int) Crypt::decrypt($this->input('user_id'));
    }

    public function messages(): array
    {
        return [
            'user_id.string' => 'El ID de usuario debe estar cifrado correctamente'
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
