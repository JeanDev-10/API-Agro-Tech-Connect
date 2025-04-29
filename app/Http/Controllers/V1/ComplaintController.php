<?php

namespace App\Http\Controllers\V1;

use App\Events\V1\UserCreatePostComplaintEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Post\StoreComplaintRequest;
use App\Http\Responses\V1\ApiResponse;
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
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */



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

    /**
     * Display the specified resource.
     */
    public function show(Complaint $complaint)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Complaint $complaint)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Complaint $complaint)
    {
        //
    }
}
