<?php

namespace App\Repository\V1\Post;

use App\Events\V1\NewPostEvent;
use App\Interfaces\V1\Post\PostRepositoryInterface;
use App\Models\V1\Post;
use App\Repository\V1\Auth\AuthRepository;
use App\Services\V1\ImageService;
use Illuminate\Support\Facades\DB;

class PostRepository implements PostRepositoryInterface
{
    public function __construct(
        protected ImageService $imageService, protected AuthRepository $authRepository
    ) {}
	public function index($filters)
	{
		$query = Post::with(['images','user.image'])
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
		 return Post::with(['images','user.image'])
            ->withCount(['comments', 'reactions'])->find($id);
    }

    public function createPostWithImages(array $data, $images = null): Post
    {
        return DB::transaction(function () use ($data, $images) {
            // Crear el post
            $user_id=$this->authRepository->userLoggedIn()->id;
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
        });
    }

    protected function attachImagesToPost(Post $post, array $images): void
    {
        $uploadedImages = $this->imageService->uploadImages(
            $images,
            'posts/images'
        );

        foreach ($uploadedImages as $image) {
            $post->images()->create([
                'image_Uuid' => $image['path'],
                'url' => $image['url'],
            ]);
        }
    }

    protected function notifyFollowers(Post $post): void
    {
        event(new NewPostEvent($post));
    }
}
