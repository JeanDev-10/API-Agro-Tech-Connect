<?php

namespace App\Http\Resources\V1\Post;

use App\Http\Resources\V1\CommentAndRate\CommentResource;
use App\Http\Resources\V1\CommentAndRate\ComplaintResource;
use App\Http\Resources\V1\CommentAndRate\ReactionResource;
use App\Http\Resources\V1\User\ImageResource;
use App\Http\Resources\V1\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => Crypt::encrypt($this->id),
            'title' => $this->title, // Ya aplica Str::ucfirst via accessor
            'description' => $this->description, // Ya aplica Str::ucfirst via accessor
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i'),

            // Relaciones principales
            'positive_reactions_count' => $this->positiveReactions()->count(),
            'negative_reactions_count' => $this->negativeReactions()->count(),
            'user' => new UserResource($this->whenLoaded('user')),

            // Relaciones con carga condicional
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'reactions' => ReactionResource::collection($this->whenLoaded('reactions')),
            'complaints' => ComplaintResource::collection($this->whenLoaded('complaints')),

            // Counts para relaciones (útil para UI sin cargar todos los datos)
            'comments_count' => $this->whenCounted('comments'),
            'images_count' => $this->whenCounted('images'),
            'reactions_count' => $this->whenCounted('reactions'),
            'complaints_count' => $this->whenCounted('complaints'),
        ];
    }
}
