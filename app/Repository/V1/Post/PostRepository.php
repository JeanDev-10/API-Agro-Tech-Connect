<?php

namespace App\Repository\V1\Post;

use App\Interfaces\V1\Post\PostRepositoryInterface;
use App\Models\V1\Post;

class PostRepository implements PostRepositoryInterface
{
	public function index($filters)
	{
		$query = Post::with(['images','user.image'])
            ->withCount(['comments', 'reactions']);

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
}
