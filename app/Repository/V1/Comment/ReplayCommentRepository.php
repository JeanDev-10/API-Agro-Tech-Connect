<?php
namespace App\Repository\V1\Comment;
use App\Interfaces\V1\Comment\ReplayCommentRepositoryInterface;
use App\Models\V1\ReplayComment;
use App\Services\V1\ImageService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReplayCommentRepository implements ReplayCommentRepositoryInterface
{
    public function __construct(
        protected ImageService $imageService,
    ) {}
	public function show($comment)
	{
        return ReplayComment::with(['images', 'user.image'])
        ->withCount(['reactions'])
        ->where('id', $comment)
        ->first();
	}
    public function deleteSpecificCommentImage(ReplayComment $comment, string $imageId)
    {

        $image = $comment->images()->find($imageId);
        if ($image == null) {
            throw new ModelNotFoundException('Imagen no encontrada');
        }

        $imagePath = $image->image_Uuid;

        // Eliminar de la base de datos
        $image->delete();

        // Eliminar del almacenamiento
        $this->imageService->deleteImage($imagePath);
        return true;
    }

}
