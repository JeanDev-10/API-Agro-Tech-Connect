<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CommentAndRate\ReplayCommentResource;
use App\Http\Responses\V1\ApiResponse;
use App\Models\V1\ReplayComment;
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
        private ReplayCommentRepository $replayCommentRepository
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
}
