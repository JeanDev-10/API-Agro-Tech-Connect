<?php

namespace App\Http\Controllers\V1;

use App\Events\V1\UserCreateCommentComplaintEvent;
use App\Events\V1\UserCreatePostComplaintEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Post\StoreComplaintRequest;
use App\Http\Responses\V1\ApiResponse;
use App\Models\V1\Comment;
use App\Models\V1\Complaint;
use App\Models\V1\Post;
use App\Notifications\V1\UserCreatePostComplaintNotification;
use App\Repository\V1\Post\ComplaintRepository;
use App\Repository\V1\Auth\AuthRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ComplaintController extends Controller
{

    public function __construct(private ComplaintRepository $complaintRepository, private AuthRepository $authRepository) {}
    



    /**
     * Report a post
     */
    public function reportPost(StoreComplaintRequest $request,  $id)
    {
        try {
            DB::beginTransaction();
            $user = $this->authRepository->userLoggedIn();
            $post = Post::find(Crypt::decrypt($id));
            if(!$post) {
                return ApiResponse::error('La publicación no existe', 404);
            }
            // Verificar límite de denuncias
            if ($this->complaintRepository->hasReachedComplaintLimit($user, $post)) {
                return ApiResponse::error('Has alcanzado el límite de denuncias para esta publicación', 422);
            }

            // Crear denuncia
            $complaint = $this->complaintRepository->createComplaint([
                'user_id' => $user->id,
                'description' => $request->description,
                'complaintable_id' => $post->id,
                'complaintable_type' => Post::class
            ]);

            // Notificar a los administradores
            event(new UserCreatePostComplaintEvent($user,$complaint, $post));
            DB::commit();
            return ApiResponse::success(
                'La denuncia ha sido registrada correctamente',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Error al registrar la denuncia: ' . $e->getMessage(), 500);
        }
    }
    public function reportComment(StoreComplaintRequest $request,  $id)
    {
        try {
            DB::beginTransaction();
            $user = $this->authRepository->userLoggedIn();
            $comment = Comment::find(Crypt::decrypt($id));
            if(!$comment) {
                return ApiResponse::error('El comentario no existe', 404);
            }
            // Verificar límite de denuncias
            if ($this->complaintRepository->hasReachedComplaintLimitComment($user, $comment)) {
                return ApiResponse::error('Has alcanzado el límite de denuncias para esta publicación', 422);
            }

            // Crear denuncia
            $complaint = $this->complaintRepository->createComplaint([
                'user_id' => $user->id,
                'description' => $request->description,
                'complaintable_id' => $comment->id,
                'complaintable_type' => Comment::class
            ]);

            // Notificar a los administradores
            event(new UserCreateCommentComplaintEvent($user,$complaint, $comment));
            DB::commit();
            return ApiResponse::success(
                'La denuncia ha sido registrada correctamente',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Error al registrar la denuncia: ' . $e->getMessage(), 500);
        }
    }

    
}
