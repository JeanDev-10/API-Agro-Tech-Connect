<?php

namespace App\Rules\V1\Auth;

use Illuminate\Support\Facades\Hash;
use App\Models\V1\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotInHistoryPassword implements ValidationRule
{

    protected User $user;
    protected bool $checkCurrent;
    public function __construct( User $user, $checkCurrent = true)
    {
        $this->user = $user;
        $this->checkCurrent = $checkCurrent;
    }


    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Verificar contra contraseña actual si está habilitado
        if ($this->checkCurrent && Hash::check($value, $this->user->password)) {
            $fail('La contraseña debe ser diferente a contraseñas anteriores');
        }

        // Verificar contra contraseñas antiguas
        foreach ($this->user->oldPasswords as $oldPassword) {
            if (Hash::check($value, $oldPassword->password)) {
                $fail('La contraseña debe ser diferente a contraseñas anteriores');
            }
        }
    }
}
