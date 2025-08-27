<?php

namespace App\Http\Requests\V1\Post;

use Illuminate\Validation\Rules\File;
use App\Http\Responses\V1\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:500',
            'description' => 'required|string|max:3000',
            'images' => 'nullable|array|max:10',
            'images.*' => [
                'image',
                File::types(['jpg', 'jpeg', 'png'])
                    ->max(3 * 1024), // 3MB
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'description.required' => 'El contenido de la publicación es obligatorio.',
            'description.max' => 'La publicación no puede exceder los 250 caracteres.',
            'images.max' => 'No puedes subir más de 10 imágenes.',
            'images.*.image' => 'Solo se permiten archivos de imagen (JPG, JPEG, PNG).',
            'images.*.max' => 'Cada imagen no debe pesar más de 3MB.',
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
