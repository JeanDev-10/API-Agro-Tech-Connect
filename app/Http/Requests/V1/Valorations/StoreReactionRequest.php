<?php

namespace App\Http\Requests\V1\Valorations;

use App\Http\Responses\V1\ApiResponse;
use Illuminate\Validation\Rules\File;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreReactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['positivo', 'negativo'])]
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'El tipo de reacción es obligatorio.',
            'type.in' => 'El tipo de reacción debe ser positivo o negativo.',
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
