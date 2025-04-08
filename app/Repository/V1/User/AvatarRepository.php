<?php

namespace App\Repository\V1\User;

use App\Interfaces\V1\User\AvatarRepositoryInterface;
use App\Models\V1\Image;
use App\Models\V1\User;
use App\Services\V1\ImageService;
use Exception;

class AvatarRepository implements AvatarRepositoryInterface
{
    protected string $folder = 'avatars';

    public function __construct(private ImageService $imageService)
    {
    }

    public function updateOrCreateAvatar( $user, $avatarFile): Image
    {
        // Eliminar imagen anterior si existe
        if ($user->image) {
            $this->deleteAvatar($user);
        }

        // Subir nueva imagen
        $uploadedImages = $this->imageService->uploadImages(
            $avatarFile,
            $this->folder,
            'public',
            true // Organizar por fecha
        );

        $uploadedImage = $uploadedImages[0];

        // Crear registro en la base de datos
        return $user->image()->create([
            'image_Uuid' => $uploadedImage['path'], // Ruta relativa completa
            'url' => $uploadedImage['url'], // URL pública
        ]);
    }

    public function deleteAvatar( $user): bool
    {
        if (!$user->image) {
            return true; // Considerar éxito si no hay imagen
        }

        // Obtener datos de la imagen
        $imagePath = $user->image->image_Uuid;
        $disk = $user->image->disk ?? 'public';

        // Eliminar archivo físico
        $fileDeleted = $this->imageService->deleteImage($imagePath, $disk);

        if (!$fileDeleted) {
            throw new Exception("No se pudo eliminar el archivo físico del avatar");
        }

        // Eliminar de la base de datos
        return $user->image()->delete();
    }
}
