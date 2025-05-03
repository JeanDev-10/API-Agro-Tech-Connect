<?php

namespace App\Http\Resources\V1\CommentAndRate;

use App\Http\Resources\V1\Post\PostResource;
use App\Http\Resources\V1\User\ImageResource;
use App\Http\Resources\V1\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $baseData = [
            'id' => Crypt::encrypt($this->id),
            'comment' => $this->comment,
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i'),

            // Relaciones principales
            'user' => new UserResource($this->whenLoaded('user')),
            'post' => new PostResource($this->whenLoaded('post')),

            // Contadores
            'replies_count' => $this->whenCounted('replies'),
            'reactions_count' => $this->whenCounted('reactions'),
            'positive_reactions_count' => $this->positiveReactions()->count(),
            'negative_reactions_count' => $this->negativeReactions()->count(),

            // Imágenes asociadas
            'images' => ImageResource::collection($this->whenLoaded('images')),
        ];

        // Relaciones paginadas (cargar solo cuando se solicitan)
        if ($this->relationLoaded('replies')) {
            $baseData['replies'] = ReplayCommentResource::collection(
                $this->replies()->with(['user', 'reactions.user'])->paginate(
                    $request->input('replies_per_page', 5),
                    ['*'],
                    'replies_page'
                )
            );

            // Incluir metadatos de paginación si está cargado
            if (method_exists($this->replies, 'currentPage')) {
                $baseData['replies_meta'] = $this->paginationMeta($this->replies);
            }
        }

        if ($this->relationLoaded('reactions')) {
            $baseData['reactions'] = ReactionResource::collection(
                $this->reactions()->with('user')->paginate(
                    $request->input('reactions_per_page', 10),
                    ['*'],
                    'reactions_page'
                )
            );
        }

        return $baseData;
    }

    /**
     * Metadatos de paginación estandarizados
     */
    protected function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'path' => $paginator->path(),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];
    }
}
