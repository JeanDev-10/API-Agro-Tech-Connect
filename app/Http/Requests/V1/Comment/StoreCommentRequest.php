<?php

namespace App\Http\Requests\V1\Comment;

use App\Http\Responses\V1\ApiResponse;
use Illuminate\Validation\Rules\File;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => 'required|string|max:1000',
            'images' => 'nullable|array|max:5',
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
            'comment.required' => 'El contenido del comentario es obligatorio.',
            'comment.max' => 'El comentario no puede exceder los 100 caracteres.',
            'images.max' => 'No puedes subir más de 5 imágenes.',
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
