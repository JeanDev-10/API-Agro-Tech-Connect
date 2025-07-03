<?php

namespace App\Repository\V1\Auth;

use App\Events\V1\UserRegisteredEvent;
use App\Http\Resources\V1\Auth\LoginResource;
use App\Http\Responses\V1\ApiResponse;
use App\Interfaces\V1\Auth\SocialAuthRepositoryInterface;
use App\Models\V1\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SocialAuthRepository implements SocialAuthRepositoryInterface
{
    public function loginWithSocialMedia(Request $request, $auth, $socialMedia)
    {
        DB::beginTransaction();

        try {
            // Verificar el token de Firebase
            $verifiedIdToken = $auth->verifyIdToken($request->token);
            $firebaseUser = $auth->getUser($verifiedIdToken->claims()->get('sub'));

            // Buscar usuario por email o firebase_uid
            $user = User::where('email', $firebaseUser->email)
                ->where('registration_method', $socialMedia)
                ->orWhere('firebase_Uuid', $firebaseUser->uid)
                ->first();

            if (!$user) {
                // Crear nuevo usuario si no existe
                $user = $this->createNewUser($firebaseUser, $socialMedia);
            } else {
                // Sincronizar campos del usuario existente
                $this->syncUserFields($user, $firebaseUser);

                // Sincronizar imagen de perfil
                $this->syncProfileImage($user, $firebaseUser);
            }
            $userResponse = User::where('registration_method', $socialMedia)->where("firebase_Uuid", "=", $firebaseUser->uid)->with('roles')->first();

            // Generar token de Sanctum
            $token = $userResponse->createToken('social-auth-token')->plainTextToken;

            DB::commit();

            return ApiResponse::success(
                'Autenticación exitosa',
                200,
                new LoginResource($userResponse, $token)
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en login con $socialMedia: " . $e->getMessage());

            return ApiResponse::error(
                'Error en autenticación',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Crea un nuevo usuario con datos de la red social
     */
    protected function createNewUser($firebaseUser, $socialMedia): User
    {
        $user = User::create([
            'username' => $this->getFirstName($firebaseUser->displayName) ?? 'Usuario',
            'name' => $this->getFirstName($firebaseUser->displayName) ?? 'Usuario',
            'lastname' => $this->getLastName($firebaseUser->displayName) ?? $socialMedia,
            'email' => $firebaseUser->email,
            'password' => '',
            'firebase_Uuid' => $firebaseUser->uid,
            'registration_method' => $socialMedia,
            'email_verified_at' => now(),
        ]);

        $user->assignRole('client_social');

        // Crear imagen de perfil
        $user->image()->create([
            'url' => $firebaseUser->photoUrl ?? null,
            'image_uuid' => null,
        ]);
        event(new UserRegisteredEvent($user));

        return $user;
    }

    /**
     * Sincroniza los campos del usuario solo si han cambiado
     */
    protected function syncUserFields(User $user, $firebaseUser): void
    {
        $updates = [];

        // Definir campos a sincronizar
        $fieldsToSync = [
            'name' => $this->getFirstName($firebaseUser->displayName) ?? $user->name,
            'username' => $this->getFirstName($firebaseUser->displayName) ?? $user->username,
            'lastname' => $this->getLastName($firebaseUser->displayName) ?? $user->lastname,
            'firebase_Uuid' => $firebaseUser->uid,
        ];

        // Verificar cambios para cada campo
        foreach ($fieldsToSync as $field => $newValue) {
            if ($user->$field != $newValue) {
                $updates[$field] = $newValue;
            }
        }

        // Actualizar solo si hay cambios
        if (!empty($updates)) {
            $user->update($updates);
        }
    }

    /**
     * Sincroniza la imagen de perfil solo si ha cambiado
     */
    protected function syncProfileImage(User $user, $firebaseUser): void
    {
        $photoUrl = $firebaseUser->photoUrl ?? null;

        if (!$photoUrl) {
            return;
        }

        // Normalizar URLs para comparación
        $currentUrl = $user->image ? rtrim($user->image->url, '/') : null;
        $newUrl = rtrim($photoUrl, '/');

        if ($currentUrl === $newUrl) {
            return; // No hay cambios
        }

        if ($user->image) {
            $user->image->update(['url' => $photoUrl]);
        } else {
            $user->image()->create(['url' => $photoUrl]);
        }
    }

    /**
     * Obtiene el primer nombre del displayName
     */
    protected function getFirstName(?string $displayName): ?string
    {
        return $displayName ? explode(' ', $displayName)[0] : null;
    }

    /**
     * Obtiene el apellido del displayName
     */
    protected function getLastName(?string $displayName): ?string
    {
        $parts = $displayName ? explode(' ', $displayName) : [];
        return count($parts) > 1 ? $parts[1] : null;
    }
}
