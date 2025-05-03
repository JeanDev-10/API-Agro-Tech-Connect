<?php

namespace App\Repository\V1\Comment;

use App\Interfaces\V1\Comment\CommentRepositoryInterface;
use App\Models\V1\Comment;
use App\Models\V1\ReplayComment;
use App\Services\V1\ImageService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CommentRepository implements CommentRepositoryInterface
{
    public function __construct(
        protected ImageService $imageService,
    ) {}
    public function getReplayComments(Comment $comment)
    {
        return $comment->replies()
            ->with(['images', 'user.image'])
            ->withCount(['reactions'])
            ->latest()
            ->paginate(10);
    }
    public function show($comment)
    {
        return Comment::with(['images', 'user.image'])
            ->withCount(['reactions', 'replies'])
            ->where('id', $comment)
            ->first();
    }
    public function createCommentReply(Comment $parentComment, array $data, ?array $images = null, $user)
    {
        $replyData = [
            'comment' => $data['comment'],
            'user_id' => $user->id,
            'comment_id' => $parentComment->id
        ];

        $reply = ReplayComment::create($replyData);

        // Subir imágenes si existen
        if ($images && count($images) > 0) {
            $uploadedImages = $this->imageService->uploadImages(
                $images,
                'comments/replies/images'
            );

            foreach ($uploadedImages as $image) {
                $reply->images()->create([
                    'image_Uuid' => $image['path'],
                    'url' => $image['url'],
                ]);
            }
        }

        return $reply->load('user.image', 'images');
    }
    public function updateReplyWithImages(ReplayComment $reply, array $data, ?array $images = null): ReplayComment
    {
        // Actualizar texto del comentario si se proporciona
        if (isset($data['comment'])) {
            $reply->update(['comment' => $data['comment']]);
        }

        // Procesar imágenes si se enviaron
        if ($images) {
            // Eliminar imágenes antiguas
            $this->deleteReplyImages($reply);

            // Subir nuevas imágenes
            $uploadedImages = $this->imageService->uploadImages(
                $images,
                'comments/replies/images'
            );

            foreach ($uploadedImages as $image) {
                $reply->images()->create([
                    'image_Uuid' => $image['path'],
                    'url' => $image['url'],
                ]);
            }
        }

        return $reply->fresh()->load('user.image', 'images');
    }

    public function deleteReplyImages(ReplayComment $reply): void
    {
        $imagePaths = $reply->images->pluck('image_Uuid')->toArray();
        $reply->images()->delete();
        $this->imageService->deleteImages($imagePaths);
    }
    public function deleteSpecificCommentImage(Comment $comment, string $imageId)
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
    public function deleteAllCommentImages(Comment $comment): bool
    {
        // Obtener paths de las imágenes
        $imagePaths = $comment->images->pluck('image_Uuid')->toArray();

        // Eliminar de la base de datos
        $comment->images()->delete();

        // Eliminar del almacenamiento
        if (!empty($imagePaths)) {
            $this->imageService->deleteImages($imagePaths);
        }

        return true;
    }

    public function getReactions($decryptedId){
        return Comment::with([
            'reactions.user.image',
            'positiveReactions.user.image',
            'negativeReactions.user.image'
        ])->findOrFail($decryptedId);
    }
}
