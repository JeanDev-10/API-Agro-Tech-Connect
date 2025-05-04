<?php

namespace App\Repository\V1\Comment;

use App\Events\V1\CommentReactionEvent;
use App\Interfaces\V1\Comment\CommentRepositoryInterface;
use App\Models\V1\Comment;
use App\Models\V1\ReplayComment;
use App\Services\V1\ImageService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CommentRepository implements CommentRepositoryInterface
{
    public function __construct(
        protected ImageService $imageService,
    ) {}
    public function getReplayComments(Comment $comment)
    {
        return $comment->replies()
            ->with(['images', 'user.image','user.ranges'])
            ->withCount(['reactions'])
            ->latest()
            ->paginate(10);
    }
    public function show($comment)
    {
        return Comment::with(['images', 'user.image','user.ranges'])
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

        return $reply->load('user.image', 'images','user.ranges');
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

        return $reply->fresh()->load('user.image', 'images','user.ranges');
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
    public function deleteComment(Comment $comment): bool
    {
        if ($comment->images()->exists()) {
            $this->deleteAllCommentImages($comment);
        }
        if ($comment->replies()->whereHas('images')->exists()) {
            $comment->replies->each(function ($reply) {
                $this->deleteReplyImages($reply);
            });
        }

        // Eliminar la respuesta
        return $comment->delete();
    }

    public function getReactions($decryptedId){
        return Comment::with([
            'reactions.user.image',
            'reactions.user.ranges',
            'positiveReactions.user.image',
            'positiveReactions.user.ranges',
            'negativeReactions.user.image',
            'negativeReactions.user.ranges',
        ])->findOrFail($decryptedId);
    }

    public function storeReaction($comment,$request,$user){

            // Verificar si el usuario ya tiene una reacción en este comentario
            $existingReaction = $comment->reactions()
                ->where('user_id', $user->id)
                ->first();


            if ($existingReaction) {
                // Si la reacción existente es del mismo tipo, lanzar excepción
                if ($existingReaction->type === $request->type) {
                    throw new Exception('Ya has reaccionado con este tipo anteriormente', 400);
                }

                // Actualizar reacción existente si es diferente
                $existingReaction->update(['type' => $request->type]);
                $reaction = $existingReaction;
            }
             else {
                // Crear nueva reacción
                $reaction = $comment->reactions()->create([
                    'type' => $request->type,
                    'user_id' => $user->id
                ]);
            }

            // Disparar evento para notificaciones
            event(new CommentReactionEvent($comment, $reaction));
            return $reaction->load('user.image','user.ranges');
    }
}
