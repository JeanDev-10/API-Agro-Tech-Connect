<?php

namespace App\Repository\V1\Post;

use App\Events\V1\NewPostEvent;
use App\Events\V1\PostReactionEvent;
use App\Http\Responses\V1\ApiResponse;
use App\Interfaces\V1\Images\ImageServiceInterface;
use App\Interfaces\V1\Post\PostRepositoryInterface;
use App\Models\V1\Comment;
use App\Models\V1\Post;
use App\Repository\V1\Auth\AuthRepository;
use App\Services\V1\ImageService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class PostRepository implements PostRepositoryInterface
{
    public function __construct(
        protected ImageServiceInterface $imageService,
        protected AuthRepository $authRepository
    ) {}
    public function index($filters)
    {
        $query = Post::with(['images', 'user.image', 'user.ranges'])
            ->withCount(['comments', 'reactions'])->latest();;

        // Filtro por año y mes
        if (isset($filters['year'])) {
            $query->whereYear('created_at', $filters['year']);
        }

        if (isset($filters['month'])) {
            $query->whereMonth('created_at', $filters['month']);
        }

        // Búsqueda avanzada por texto o palabras clave
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->paginate(10);
    }
    public function show($id)
    {
        return Post::with(['images', 'user.image', 'user.ranges'])
            ->withCount(['comments', 'reactions'])->find($id);
    }

    public function createPostWithImages(array $data, $images = null): Post
    {
        // Crear el post
        $user_id = $this->authRepository->userLoggedIn()->id;
        $post = Post::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'user_id' => $user_id,
        ]);

        // Subir imágenes si existen
        if ($images && count($images) > 0) {
            $this->attachImagesToPost($post, $images);
        }

        // Notificar a seguidores
        $this->notifyFollowers($post);

        return $post->load('images', 'user.image');
    }

    public function attachImagesToPost(Post $post, array $images): void
    {
        $uploadedImages = $this->imageService->uploadImages(
            $images,
            'agrotechconnect/posts/images'
        );

        foreach ($uploadedImages as $image) {
            $post->images()->create([
                'image_Uuid' => $image['path'],
                'url' => $image['url'],
            ]);
        }
    }

    public function notifyFollowers(Post $post): void
    {
        event(new NewPostEvent($post));
    }

    public function updatePostWithImages(Post $post, array $data, $images = null): Post
    {
        // Actualizar datos básicos
        $post->update([
            'title' => $data['title'] ?? $post->title,
            'description' => $data['description'] ?? $post->description,
        ]);

        // Procesar imágenes si se enviaron
        if ($images) {
            // Eliminar imágenes antiguas
            //$this->deleteOldImages($post);

            // Subir nuevas imágenes
            $this->attachImagesToPost($post, $images);
        }

        return $post->fresh()->load('images', 'user.image');
    }

    public function deleteOldImages(Post $post): void
    {
        // Obtener paths de las imágenes antiguas
        $oldImages = $post->images()->get();
        $pathsToDelete = $oldImages->pluck('image_Uuid')->toArray();

        // Eliminar de la base de datos
        $post->images()->delete();

        // Eliminar del almacenamiento
        if (!empty($pathsToDelete)) {
            $this->imageService->deleteImages($pathsToDelete);
        }
    }
    public function deletePostWithRelations(Post $post): void
    {
        // Eliminar imágenes del storage y BD
        $this->deletePostImages($post);

        // Eliminar comentarios y sus relaciones
        $this->deleteCommentsWithRelations($post);

        // Finalmente eliminar el post
        $post->delete();
    }

    public function deletePostImages(Post $post): void
    {
        $imagePaths = $post->images->pluck('image_Uuid')->toArray();

        // Eliminar de BD
        $post->images()->delete();

        // Eliminar del storage
        if (!empty($imagePaths)) {
            $this->imageService->deleteImages($imagePaths);
        }
    }

    public function deleteCommentsWithRelations(Post $post): void
    {
        $post->comments->each(function ($comment) {
            // Eliminar respuestas a comentarios y sus imágenes
            $this->deleteRepliesWithImages($comment);

            // Eliminar imágenes del comentario principal
            if ($comment->images->isNotEmpty()) {
                $this->deleteCommentImages($comment);
            }

            // Eliminar el comentario
            $comment->delete();
        });
    }

    public function deleteRepliesWithImages($comment): void
    {
        $comment->replies->each(function ($reply) {
            // Eliminar imágenes de las respuestas
            if ($reply->images->isNotEmpty()) {
                $imagePaths = $reply->images->pluck('image_Uuid')->toArray();
                $this->imageService->deleteImages($imagePaths);
                $reply->images()->delete();
            }

            $reply->delete();
        });
    }
    public function deleteCommentImages($comment): void
    {
        $imagePaths = $comment->images->pluck('image_Uuid')->toArray();
        $comment->images()->delete();
        $this->imageService->deleteImages($imagePaths);
    }
    public function deleteAllPostImages(Post $post): bool
    {
        // Obtener paths de las imágenes
        $imagePaths = $post->images->pluck('image_Uuid')->toArray();

        // Eliminar de la base de datos
        $post->images()->delete();

        // Eliminar del almacenamiento
        if (!empty($imagePaths)) {
            $this->imageService->deleteImages($imagePaths);
        }

        return true;
    }
    /**
     * Elimina una imagen específica de un post
     *
     * @param Post $post
     * @param string $imageId
     * @return bool
     * @throws \Exception
     */
    public function deleteSpecificPostImage(Post $post, string $imageId)
    {

        $image = $post->images()->find($imageId);
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
    public function getPostComments(Post $post)
    {
        return $post->comments()
            ->with(['images', 'user.image', 'user.ranges'])
            ->withCount(['replies', 'reactions'])
            ->latest()
            ->paginate(10);
    }


    public function createComment(Post $post, array $data, ?array $images = null, $user)
    {
        $commentData = [
            'comment' => $data['comment'],
            'user_id' => $user->id,
        ];

        $comment = $post->comments()->create($commentData);

        // Subir imágenes si existen
        if ($images && count($images) > 0) {
            $uploadedImages = $this->imageService->uploadImages(
                $images,
                'agrotechconnect/comments/images'
            );

            foreach ($uploadedImages as $image) {
                $comment->images()->create([
                    'image_Uuid' => $image['path'],
                    'url' => $image['url'],
                ]);
            }
        }

        return $comment->load('user.image', 'images');
    }

    public function updateCommentWithImages(Comment $comment, array $data, $images = null): Comment
    {
        // Actualizar texto del comentario si se proporciona
        if (isset($data['comment'])) {
            $comment->update(['comment' => $data['comment']]);
        }

        // Procesar imágenes si se enviaron
        if ($images) {
            if($comment->images->isNotEmpty()) {
                // Eliminar imágenes antiguas en el caso que existan
                //$this->deleteCommentImages($comment);
            }
            // Subir nuevas imágenes
            $uploadedImages = $this->imageService->uploadImages(
                $images,
                'agrotechconnect/comments/images'
            );
            foreach ($uploadedImages as $image) {
                $comment->images()->create([
                    'image_Uuid' => $image['path'],
                    'url' => $image['url'],
                ]);
            }
        }

        return $comment->fresh()->load('user.image', 'images');
    }
    public function getReactions($decryptedId)
    {
        return Post::with([
            'reactions.user.image',
            'positiveReactions.user.image',
            'negativeReactions.user.image'
        ])->findOrFail($decryptedId);
    }
    public function storeReaction($post, $request, $user)
    {

        // Verificar si el usuario ya tiene una reacción en este comentario
        $existingReaction = $post->reactions()
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
            $reaction = $post->reactions()->create([
                'type' => $request->type,
                'user_id' => $user->id
            ]);
        }

        // Disparar evento para notificaciones
        event(new PostReactionEvent($post, $reaction));
        return $reaction->load('user.image', 'user.ranges');
    }
    public function deleteReaction($post, $user)
    {
        $existingReaction = $post->reactions()
            ->where('user_id', $user->id)
            ->first();
        if ($existingReaction!=null) {
            $existingReaction->delete();
        } else {
            throw new Exception('Aún no has reaccionado a esta publicación', 400);
        }
    }
}
