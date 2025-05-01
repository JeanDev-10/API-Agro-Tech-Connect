<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CommentAndRate\CommentResource;
use App\Http\Resources\V1\CommentAndRate\ReplayCommentResource;
use App\Http\Responses\V1\ApiResponse;
use App\Models\V1\Comment;
use App\Repository\V1\Comment\CommentRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class CommentController extends Controller
{
    public function __construct(
        private CommentRepository $commentRepository
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comment $comment)
    {
        //
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
}
