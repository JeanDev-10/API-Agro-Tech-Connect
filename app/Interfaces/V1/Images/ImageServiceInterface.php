<?php
namespace App\Interfaces\V1\Images;

interface ImageServiceInterface{
     public function uploadImages(
        $files,
        string $folder,
        string $disk = 'public',
        bool $organizeByDate = true
    ): array;
    public function deleteImage(string $filePath, string $disk = 'public'): bool;
    public function deleteImages(array $filePaths, string $disk = 'public'): bool;
}
