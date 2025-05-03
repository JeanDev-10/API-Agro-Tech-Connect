<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Valorations\StoreReactionRequest;
use App\Http\Resources\V1\CommentAndRate\ReactionResource;
use App\Http\Resources\V1\CommentAndRate\ReplayCommentResource;
use App\Http\Responses\V1\ApiResponse;
use App\Models\V1\ReplayComment;
use App\Repository\V1\Auth\AuthRepository;
use App\Repository\V1\Comment\ReplayCommentRepository;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;

class ReplayCommentController extends Controller
{
    public function __construct(
        private ReplayCommentRepository $replayCommentRepository,
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
    public function show($replayComment_id)
    {
        try {
            $comment = $this->replayCommentRepository->show(Crypt::decrypt($replayComment_id));
            if (!$comment) {
                return ApiResponse::error("No se encontró la respuesta a comentario", 404);
            }
            return ApiResponse::success(
                'Respuesta a comentario encontrado',
                200,
                new ReplayCommentResource($comment)
            );
        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error" . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReplayComment $replayComment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReplayComment $replayComment)
    {
        //
    }

    public function deleteImage(string $id, string $imageId)
    {
        try {
            DB::beginTransaction();
            $comment = ReplayComment::find(Crypt::decrypt($id));
            if (!$comment) {
                return ApiResponse::error("No se encontró la respuesta a comentario", 404);
            }
            $image_id = Crypt::decrypt($imageId);
            $this->authorize('update', $comment);
            $this->replayCommentRepository->deleteSpecificCommentImage($comment, $image_id);
            DB::commit();
            return ApiResponse::success(
                'Imágen de respuesta eliminada exitosamente',
                200
            );
        } catch (UnauthorizedException $e) {
            DB::rollBack();
            return ApiResponse::error(
                "No puedes eliminar la imagen de esta respuesta",
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
                'Error al eliminar imágen de una respuesta: ' . $e->getMessage(),
                500
            );
        }
    }
    public function deleteImages(string $encryptedId)
    {
        try {
            DB::beginTransaction();
            $id = Crypt::decrypt($encryptedId);
            $comment = ReplayComment::findOrFail($id);
            $this->authorize('update', $comment);
            $this->replayCommentRepository->deleteAllCommentImages($comment);
            DB::commit();
            return ApiResponse::success(
                'Imágenes de la respuesta eliminadas exitosamente',
                200
            );
        } catch (UnauthorizedException $e) {
            DB::rollBack();
            return ApiResponse::error(
                "No puedes eliminar las imágenes de esta respuesta",
                statusCode: 403
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error('Respuesta no encontrado', 404);
        } catch (Exception $e) {
            DB::rollBack();

            return ApiResponse::error(
                'Error al eliminar imágenes de la respuesta: ' . $e->getMessage(),
                500
            );
        }
    }
    public function getReactions($id)
    {
        try {
            $decryptedId = Crypt::decrypt($id);
            $comment = $this->replayCommentRepository->getReactions($decryptedId);
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
            return ApiResponse::error('Respuesta no encontrado', 404);
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponse::error(
                'Error al ver reacciones del Respuesta: ' . $e->getMessage(),
                500
            );
        }
    }
    public function createReaction(StoreReactionRequest $request, $id)
    {
        try {
            DB::beginTransaction();
            $decryptedId = Crypt::decrypt($id);
            $replay = ReplayComment::findOrFail($decryptedId);
            $user = $this->authRepository->userLoggedIn();
            $reaction = $this->replayCommentRepository->storeReaction($replay, $request, $user);
            DB::commit();
            return ApiResponse::success(
                'Reacción registrada correctamente',
                201,
                New ReactionResource($reaction)
            );
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Respuesta no encontrado', 404);
        } catch (Exception $e) {
            DB::rollBack();
            // Manejar específicamente el error de reacción duplicada
            if ($e->getCode() === 400) {
                return ApiResponse::error($e->getMessage(), 400);
            }
            return ApiResponse::error('Error al registrar reacción: ' . $e->getMessage(), 500);
        }
    }
}
