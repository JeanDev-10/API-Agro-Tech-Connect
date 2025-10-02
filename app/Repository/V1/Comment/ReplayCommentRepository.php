<?php

namespace App\Repository\V1\Comment;

use App\Events\V1\ReplayCommentReactionEvent;
use App\Interfaces\V1\Comment\ReplayCommentRepositoryInterface;
use App\Interfaces\V1\Images\ImageServiceInterface;
use App\Models\V1\ReplayComment;
use App\Services\V1\ImageService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReplayCommentRepository implements ReplayCommentRepositoryInterface
{
    public function __construct(
        protected ImageServiceInterface $imageService,
    ) {}
    public function show($comment)
    {
        return ReplayComment::with(['images', 'user.image', 'user.ranges'])
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
    public function deleteAllCommentImages(ReplayComment $comment): bool
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
    public function getReactions($decryptedId)
    {
        return ReplayComment::with([
            'reactions.user.image',
            'reactions.user.ranges',
            'positiveReactions.user.image',
            'positiveReactions.user.ranges',
            'negativeReactions.user.image',
            'negativeReactions.user.ranges'
        ])->findOrFail($decryptedId);
    }
    public function storeReaction($replay, $request, $user)
    {

        // Verificar si el usuario ya tiene una reacción en este comentario
        $existingReaction = $replay->reactions()
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
        } else {
            // Crear nueva reacción
            $reaction = $replay->reactions()->create([
                'type' => $request->type,
                'user_id' => $user->id
            ]);
        }

        // Disparar evento para notificaciones
        event(new ReplayCommentReactionEvent($replay, $reaction));
        return $reaction->load('user.image', 'user.ranges');
    }
    public function deleteReplayComment(ReplayComment $replayComment): bool
    {
        // Eliminar todas las imágenes asociadas a la respuesta
        if ($replayComment->images()->exists()) {
            $this->deleteAllCommentImages($replayComment);
        }

        // Eliminar la respuesta
        return $replayComment->delete();
    }
    public function deleteReaction($comment, $user)
    {
        $existingReaction = $comment->reactions()
            ->where('user_id', $user->id)
            ->first();
        if ($existingReaction!=null) {
            $existingReaction->delete();
        } else {
            throw new Exception('Aún no has reaccionado a esta respuesta', 400);
        }
    }
}
