<?php

namespace App\Services\V1;

use App\Interfaces\V1\Images\ImageServiceInterface;
use Exception;
use Illuminate\Support\Str;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary as CloudinaryFacade;

class ImageServiceCloudinary implements ImageServiceInterface
{
    public function uploadImages(
        $files,
        string $folder,
        string $disk = 'cloudinary',
        bool $organizeByDate = true
    ): array {
        if (!is_array($files)) {
            $files = [$files];
        }

        $uploadedImages = [];

        foreach ($files as $file) {
            if (!$file->isValid()) {
                throw new Exception("Archivo inválido o no cargado correctamente.");
            }

            // Generamos un nombre único y limpio para evitar problemas con espacios
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $folder;

            // Organizar las imágenes por fecha si es necesario
            if ($organizeByDate) {
                $path .= '/' . date('Y/m/d');
            }

            try {
                // Usar Cloudinary API para subir la imagen
                $uploadResult = CloudinaryFacade::uploadApi()->upload($file->getRealPath(), [
                    'folder' => $path,
                    'public_id' => $fileName,  // Usamos el nombre limpio y único
                    'resource_type' => 'auto' // Detecta automáticamente el tipo de archivo
                ]);

                // Agregamos la información de la imagen subida al arreglo
                $uploadedImages[] = [
                    'file_name' => $fileName,
                    'path' => $uploadResult['secure_url'],
                    'url' => $uploadResult['secure_url'],
                    'full_path' => $uploadResult['secure_url'],
                    'public_id' => $uploadResult['public_id'], // Útil para eliminar después
                ];

            } catch (Exception $e) {
                throw new Exception("Error al subir la imagen a Cloudinary: " . $e->getMessage());
            }
        }

        return $uploadedImages;
    }

    public function deleteImage(string $filePath, string $disk = 'cloudinary'): bool
    {
        try {
            // Extraer el public_id de la URL de Cloudinary
            $publicId = $this->extractPublicIdFromUrl($filePath);

            if (!$publicId) {
                throw new Exception("No se pudo extraer el public_id de la URL: " . $filePath);
            }

            // Usar Cloudinary API para eliminar la imagen
            $result = CloudinaryFacade::uploadApi()->destroy($publicId);

            return isset($result['result']) && $result['result'] === 'ok';

        } catch (Exception $e) {
            throw new Exception("Error eliminando imagen de Cloudinary: " . $e->getMessage());
        }
    }

    public function deleteImages(array $filePaths, string $disk = 'cloudinary'): bool
    {
        try {
            $allDeleted = true;

            foreach ($filePaths as $path) {
                if (!empty($path) && !$this->deleteImage($path)) {
                    $allDeleted = false;
                }
            }

            return $allDeleted;
        } catch (Exception $e) {
            throw new Exception("Error eliminando imágenes de Cloudinary: " . $e->getMessage());
        }
    }

    /**
     * Extrae el public_id de una URL de Cloudinary
     */
    private function extractPublicIdFromUrl(string $url): ?string
    {
        // Primero, decodificamos la URL para manejar caracteres codificados
        $url = urldecode($url);

        // Ajustamos el patrón para coincidir correctamente con cualquier nombre de archivo
        $pattern = '/\/image\/upload\/(?:v\d+\/)?(.+)\.[a-zA-Z0-9]+$/';

        if (preg_match($pattern, $url, $matches)) {
            return $matches[1]; // Devolver el public_id limpio
        }

        // Si no se encuentra con la expresión regular, intentamos con pathinfo
        return pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
    }
}
