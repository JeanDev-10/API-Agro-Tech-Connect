<?php

namespace App\Http\Controllers\V1;

use App\Events\V1\NewCommentEvent;
use App\Events\V1\NewReplyEvent;
use App\Events\V1\PostDeletedByAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Comment\StoreCommentRequest;
use App\Http\Requests\V1\Comment\UpdateCommentRequest;
use App\Http\Requests\V1\Post\StorePostRequest;
use App\Http\Requests\V1\Post\UpdatePostRequest;
use App\Http\Resources\V1\CommentAndRate\CommentResource;
use App\Http\Resources\V1\CommentAndRate\ReplayCommentResource;
use App\Http\Resources\V1\Post\PostResource;
use App\Http\Responses\V1\ApiResponse;
use App\Models\V1\Comment;
use App\Models\V1\Post;
use App\Repository\V1\Auth\AuthRepository;
use App\Repository\V1\Comment\CommentRepository;
use App\Repository\V1\Post\PostRepository;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class PostController extends Controller
{
    public function __construct(private PostRepository $postRepository, private AuthRepository $authRepository, private CommentRepository $commentRepository) {}

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

    public function createPostComments(StoreCommentRequest $request, $id)
    {
        try {
            DB::beginTransaction();
            $post = Post::findOrFail(Crypt::decrypt($id))->first();
            $userLogged = $this->authRepository->userProfile();

            // Crear el comentario
            $comment = $this->postRepository->createComment(
                $post,
                $request->validated(),
                $request->file('images'),
                $userLogged
            );

            // Notificar al dueño del post
            event(new NewCommentEvent($post, $comment, $userLogged));

            DB::commit();

            return ApiResponse::success(
                'Comentario agregado exitosamente',
                201,
                new CommentResource($comment)
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return ApiResponse::error('Publicación no encontrada', 404);
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponse::error(
                'Error al agregar el comentario: ' . $e->getMessage(),
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
 * Update the specified comment.
 */
public function updatePostComments(UpdateCommentRequest $request, $postId, $commentId)
{
    try {
        DB::beginTransaction();
        
        // Decrypt IDs
        $decryptedPostId = Crypt::decrypt($postId);
        $decryptedCommentId = Crypt::decrypt($commentId);
        // Obtener el comentario
        $comment = Comment::where('post_id', $decryptedPostId)
                         ->findOrFail($decryptedCommentId);
        // Autorizar que solo el dueño puede editar
        $this->authorize('update', $comment);
        
        // Actualizar el comentario
        $updatedComment = $this->postRepository->updateCommentWithImages(
            $comment,
            $request->validated(),
            $request->file('images')
        );
        
        DB::commit();
        
        return ApiResponse::success(
            'Comentario actualizado exitosamente',
            200,
            new CommentResource($updatedComment)
        );
    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return ApiResponse::error('Comentario no encontrado', 404);
    } catch (UnauthorizedException $e) {
        DB::rollBack();
        return ApiResponse::error(
            "No puedes actualizar este comentario",
            statusCode: 403
        );
    } catch (Exception $e) {
        DB::rollBack();
        return ApiResponse::error(
            'Error al actualizar el comentario: ' . $e->getMessage(),
            500
        );
    }
}



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $id = Crypt::decrypt($id);
            $post = Post::findOrFail($id);

            $this->authorize('delete', $post);

            /* $this->postRepository->deletePostWithRelations($post); */
            $userLogged = $this->authRepository->userProfile();
            if ($userLogged->hasRole('admin')) {
                event(new PostDeletedByAdmin($post, $userLogged));
            }
            DB::commit();
            return ApiResponse::success(
                'Publicación eliminada exitosamente',
                200
            );
        } catch (UnauthorizedException $e) {
            DB::rollBack();
            return ApiResponse::error(
                "No puedes eliminar esta publicación",
                statusCode: 403
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error('Publicación no encontrada', 404);
        } catch (Exception $e) {
            DB::rollBack();

            return ApiResponse::error(
                'Error al eliminar la publicación: ' . $e->getMessage(),
                500
            );
        }
    }

    public function deleteImages(string $encryptedId)
    {
        try {
            DB::beginTransaction();
            $id = Crypt::decrypt($encryptedId);
            $post = Post::findOrFail($id);
            $this->authorize('update', $post);
            $this->postRepository->deleteAllPostImages($post);
            DB::commit();
            return ApiResponse::success(
                'Imágenes de la publicación eliminadas exitosamente',
                200
            );
        } catch (UnauthorizedException $e) {
            DB::rollBack();
            return ApiResponse::error(
                "No puedes eliminar las imágenes de esta publicación",
                statusCode: 403
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error('Publicación no encontrada', 404);
        } catch (Exception $e) {
            DB::rollBack();

            return ApiResponse::error(
                'Error al eliminar imágenes de la publicación: ' . $e->getMessage(),
                500
            );
        }
    }
    public function deleteImage(string $encryptedPostId, string $imageId)
    {
        try {
            DB::beginTransaction();
            $post = Post::find(Crypt::decrypt($encryptedPostId));
            if (!$post) {
                return ApiResponse::error("No se encontró el post", 404);
            }
            $image_id = Crypt::decrypt($imageId);
            $this->authorize('update', $post);
            $this->postRepository->deleteSpecificPostImage($post, $image_id);
            DB::commit();
            return ApiResponse::success(
                'Imágen de la publicación eliminada exitosamente',
                200
            );
        } catch (UnauthorizedException $e) {
            DB::rollBack();
            return ApiResponse::error(
                "No puedes eliminar la imagen de esta publicación",
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
                'Error al eliminar imágen de la publicación: ' . $e->getMessage(),
                500
            );
        }
    }
    public function getPostComments($id)
    {
        try {
            $post = Post::find(Crypt::decrypt($id));
            if (!$post) {
                return ApiResponse::error("No se encontró el post", 404);
            }
            $comments = $this->postRepository->getPostComments($post);
            if ($comments->isEmpty()) {
                return ApiResponse::error("No se encontraron comentarios", 404);
            }
            return ApiResponse::success(
                'Listado de comentarios',
                200,
                $comments->through(function ($comment) {
                    return new CommentResource($comment);
                })
            );
        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error" . $e->getMessage(), 500);
        }
    }
    public function createReplayComments(StoreCommentRequest $request, $postId, $commentId)
    {
        try {
            DB::beginTransaction();

            // Decrypt IDs
            $decryptedPostId = Crypt::decrypt($postId);
            $decryptedCommentId = Crypt::decrypt($commentId);

            // Obtener el comentario padre
            $parentComment = Comment::where('post_id', $decryptedPostId)
                ->findOrFail($decryptedCommentId);

            $userLogged = $this->authRepository->userProfile();

            // Crear la respuesta
            $reply = $this->commentRepository->createCommentReply(
                $parentComment,
                $request->validated(),
                $request->file('images'),
                $userLogged
            );

            // Notificar al dueño del comentario
            event(new NewReplyEvent($parentComment, $reply, $userLogged));

            DB::commit();

            return ApiResponse::success(
                'Respuesta agregada exitosamente',
                201,
                new ReplayCommentResource($reply)
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return ApiResponse::error('Comentario no encontrado', 404);
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponse::error(
                'Error al agregar la respuesta: ' . $e->getMessage(),
                500
            );
        }
    }
}
