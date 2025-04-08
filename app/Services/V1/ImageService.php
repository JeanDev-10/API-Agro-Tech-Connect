<?php

namespace App\Services\V1;

use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    /**
     * Sube una o varias imágenes al almacenamiento local
     *
     * @param mixed $files Archivo(s) a subir
     * @param string $folder Carpeta base donde se guardará(n)
     * @param string $disk Disco de almacenamiento (por defecto 'public')
     * @param bool $organizeByDate Si se organiza por año/mes (default: true)
     * @return array
     * @throws Exception
     */
    public function uploadImages(
        $files,
        string $folder,
        string $disk = 'public',
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

            // Generar nombre único
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $folder;

            // Organización por fecha si está activado
            if ($organizeByDate) {
                $path .= '/' . date('Y/m');
            }

            // Crear directorio si no existe
            Storage::disk($disk)->makeDirectory($path);

            // Guardar el archivo
            $storedPath = $file->storeAs($path, $fileName, $disk);

            if (!$storedPath) {
                throw new Exception("Error al guardar la imagen en el almacenamiento local.");
            }

            $uploadedImages[] = [
                'file_name' => $fileName,
                'path' => $storedPath, // Ruta relativa en el storage
                'url' => Storage::disk($disk)->url($storedPath),
                'full_path' => Storage::disk($disk)->path($storedPath),
            ];
        }

        return $uploadedImages;
    }

    /**
     * Elimina una imagen del almacenamiento local
     *
     * @param string $filePath Ruta relativa del archivo a eliminar
     * @param string $disk Disco de almacenamiento
     * @return bool
     * @throws Exception
     */
    public function deleteImage(string $filePath, string $disk = 'public'): bool
    {
        try {
            // Verificar existencia y eliminar
            if (Storage::disk($disk)->exists($filePath)) {
                return Storage::disk($disk)->delete($filePath);
            }

            // Considerar éxito si el archivo ya no existe
            return true;
        } catch (Exception $e) {
            throw new Exception("Error eliminando imagen: " . $e->getMessage());
        }
    }

    /**
     * Elimina múltiples imágenes del almacenamiento local
     *
     * @param array $filePaths Rutas relativas de los archivos a eliminar
     * @param string $disk Disco de almacenamiento
     * @return bool
     * @throws Exception
     */
    public function deleteImages(array $filePaths, string $disk = 'public'): bool
    {
        try {
            $allDeleted = true;

            foreach ($filePaths as $path) {
                if (!empty($path) && !$this->deleteImage($path, $disk)) {
                    $allDeleted = false;
                }
            }

            return $allDeleted;
        } catch (Exception $e) {
            throw new Exception("Error eliminando imágenes: " . $e->getMessage());
        }
    }
}
