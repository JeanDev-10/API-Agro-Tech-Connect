<?php

namespace App\Http\Controllers\V1;

use App\Events\V1\CommentDeletedByAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Valorations\StoreReactionRequest;
use App\Http\Resources\V1\CommentAndRate\CommentResource;
use App\Http\Resources\V1\CommentAndRate\ReactionResource;
use App\Http\Resources\V1\CommentAndRate\ReplayCommentResource;
use App\Http\Responses\V1\ApiResponse;
use App\Models\V1\Comment;
use App\Repository\V1\Auth\AuthRepository;
use App\Repository\V1\Comment\CommentRepository;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;

class CommentController extends Controller
{
    public function __construct(
        private CommentRepository $commentRepository,
        private AuthRepository $authRepository,
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($comment_id)
    {
        try {
            $comment = $this->commentRepository->show(Crypt::decrypt($comment_id));
            if (!$comment) {
                return ApiResponse::error("No se encontró el comentario", 404);
            }
            return ApiResponse::success(
                'Comentario encontrado',
                200,
                new CommentResource($comment)
            );
        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error" . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Comment $comment)
    {
        //
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $id = Crypt::decrypt($id);
            $comment = Comment::findOrFail($id);

            $this->authorize('delete', $comment);

             $this->commentRepository->deleteComment($comment);
            $userLogged = $this->authRepository->userProfile();
            event(new CommentDeletedByAdmin($comment, $userLogged));
            DB::commit();
            return ApiResponse::success(
                'Respuesta eliminada exitosamente',
                200
            );
        } catch (UnauthorizedException $e) {
            DB::rollBack();
            return ApiResponse::error(
                "No puedes eliminar este comentario",
                statusCode: 403
            );
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Comentario no encontrado', 404);
        } catch (Exception $e) {
            DB::rollBack();

            return ApiResponse::error(
                'Error al eliminar el comentario: ' . $e->getMessage(),
                500
            );
        }
    }
    public function getReplayComments($id)
    {
        try {
            $comment = Comment::find(Crypt::decrypt($id));
            if (!$comment) {
                return ApiResponse::error("No se encontró el comentario", 404);
            }
            $comments = $this->commentRepository->getReplayComments($comment);
            if ($comments->isEmpty()) {
                return ApiResponse::error("No se encontraron respuestas a comentarios", 404);
            }
            return ApiResponse::success(
                'Listado de respuestas a comentarios',
                200,
                $comments->through(function ($comment) {
                    return new ReplayCommentResource($comment);
                })
            );
        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error" . $e->getMessage(), 500);
        }
    }
    public function deleteImage(string $id, string $imageId)
    {
        try {
            DB::beginTransaction();
            $comment = Comment::find(Crypt::decrypt($id));
            if (!$comment) {
                return ApiResponse::error("No se encontró el comentario", 404);
            }
            $image_id = Crypt::decrypt($imageId);
            $this->authorize('update', $comment);
            $this->commentRepository->deleteSpecificCommentImage($comment, $image_id);
            DB::commit();
            return ApiResponse::success(
                'Imágen del comentario eliminada exitosamente',
                200
            );
        } catch (UnauthorizedException $e) {
            DB::rollBack();
            return ApiResponse::error(
                "No puedes eliminar la imagen de este comentario",
                statusCode: 403
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return ApiResponse::error(
                'Imágen no encontrada',
                404
            );
        } catch (Exception $e) {
            DB::rollBack();

            return ApiResponse::error(
                'Error al eliminar imágen de un comentario: ' . $e->getMessage(),
                500
            );
        }
    }
    public function deleteImages(string $encryptedId)
    {
        try {
            DB::beginTransaction();
            $id = Crypt::decrypt($encryptedId);
            $comment = Comment::findOrFail($id);
            $this->authorize('update', $comment);
            $this->commentRepository->deleteAllCommentImages($comment);
            DB::commit();
            return ApiResponse::success(
                'Imágenes del comentario eliminadas exitosamente',
                200
            );
        } catch (UnauthorizedException $e) {
            DB::rollBack();
            return ApiResponse::error(
                "No puedes eliminar las imágenes de este comentario",
                statusCode: 403
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error('Comentario no encontrado', 404);
        } catch (Exception $e) {
            DB::rollBack();

            return ApiResponse::error(
                'Error al eliminar imágenes del comentario: ' . $e->getMessage(),
                500
            );
        }
    }
    public function getReactions($id)
    {
        try {
            $decryptedId = Crypt::decrypt($id);
            $comment = $this->commentRepository->getReactions($decryptedId);
            return ApiResponse::success(
                'Listado de reacciones',
                200,
                [
                    'all_reactions' => ReactionResource::collection($comment->reactions),
                    'positive_reactions' => ReactionResource::collection($comment->positiveReactions),
                    'negative_reactions' => ReactionResource::collection($comment->negativeReactions),
                    'counts' => [
                        'total' => $comment->reactions->count(),
                        'positive' => $comment->positiveReactions->count(),
                        'negative' => $comment->negativeReactions->count()
                    ]
                ]
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return ApiResponse::error('Comentario no encontrado', 404);
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponse::error(
                'Error al ver reacciones del comentario: ' . $e->getMessage(),
                500
            );
        }
    }
    public function createReaction(StoreReactionRequest $request, $commentId)
    {
        try {
            DB::beginTransaction();
            $decryptedId = Crypt::decrypt($commentId);
            $comment = Comment::findOrFail($decryptedId);
            $user = $this->authRepository->userLoggedIn();
            $reaction = $this->commentRepository->storeReaction($comment, $request, $user);
            DB::commit();
            return ApiResponse::success(
                'Reacción registrada correctamente',
                201,
                New ReactionResource($reaction)
            );
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Comentario no encontrado', 404);
        } catch (Exception $e) {
            DB::rollBack();
            // Manejar específicamente el error de reacción duplicada
            if ($e->getCode() === 400) {
                return ApiResponse::error($e->getMessage(), 400);
            }
            return ApiResponse::error('Error al registrar reacción: ' . $e->getMessage(), 500);
        }
    }
    public function deleteReaction($id)
    {
        try {
            DB::beginTransaction();
            $decryptedId = Crypt::decrypt($id);
            $comment = Comment::findOrFail($decryptedId);
            $user = $this->authRepository->userLoggedIn();
             $this->commentRepository->deleteReaction($comment, $user);
            DB::commit();
            return ApiResponse::success(
                'Reacción eliminada correctamente',
                204,
            );
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Comentario no encontrado', 404);
        } catch (Exception $e) {
            DB::rollBack();
            // Manejar específicamente el error de reacción duplicada
            if ($e->getCode() === 400) {
                return ApiResponse::error($e->getMessage(), 400);
            }
            return ApiResponse::error('Error al eliminar reacción: ' . $e->getMessage(), 500);
        }
    }
}
