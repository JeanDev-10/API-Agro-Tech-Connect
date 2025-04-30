<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Post\StorePostRequest;
use App\Http\Requests\V1\Post\UpdatePostRequest;
use App\Http\Resources\V1\Post\PostResource;
use App\Http\Responses\V1\ApiResponse;
use App\Models\V1\Post;
use App\Repository\V1\Post\PostRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class PostController extends Controller
{
    public function __construct(private PostRepository $postRepository) {}

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['year', 'month', 'search']);
            $posts = $this->postRepository->index($filters);
            if ($posts->isEmpty()) {
                return ApiResponse::error("No se encontraron posts", 404);
            }
            return ApiResponse::success(
                'Listado de Posts',
                200,
                $posts->through(function ($post) {
                    return new PostResource($post);
                })
            );
        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error" . $e->getMessage(), 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        try {
            DB::beginTransaction();
            $post = $this->postRepository->createPostWithImages(
                $request->validated(),
                $request->file('images')
            );
            DB::commit();
            return ApiResponse::success(
                'Publicación creada exitosamente',
                201,
                new PostResource($post)
            );
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponse::error(
                'Error al crear la publicación: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $post_id = Crypt::decrypt($id);
            $post = $this->postRepository->show($post_id);
            if (!$post) {
                return ApiResponse::error("No se encontró el post", 404);
            }
            return ApiResponse::success(
                'Post encontrado',
                200,
                new PostResource($post)
            );
        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error" . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, $id)
    {
        try {
            DB::beginTransaction();
            $post = Post::find(Crypt::decrypt($id));
            if (!$post) {
                return ApiResponse::error("No se encontró el post", 404);
            }
            // Autorizar la acción
            $this->authorize('update', $post);

            $updatedPost = $this->postRepository->updatePostWithImages(
                $post,
                $request->validated(),
                $request->file('images')
            );
            DB::commit();

            return ApiResponse::success(
                'Publicación actualizada exitosamente',
                200,
                new PostResource($updatedPost)
            );
        } catch (UnauthorizedException $e) {
            DB::rollBack();
            return ApiResponse::error(
                "No puedes actualizar esta publicación",
                statusCode: 403
            );
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponse::error(
                'Error al actualizar la publicación: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        //
    }
}
