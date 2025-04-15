<?php

namespace App\Http\Resources\V1\CommentAndRate;

use App\Http\Resources\V1\Post\PostResource;
use App\Http\Resources\V1\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class ReactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        // Determina el resource adecuado para el modelo reactionable
        $reactionableResource = $this->determineReactionableResource();

        return [
            'id' => Crypt::encrypt($this->id),
            'type' => $this->type,
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i'),

            // Relación con usuario
            'user' => new UserResource($this->whenLoaded('user')),

            // Relación polimórfica
            'reactionable_type' => $this->reactionable_type,
            'reactionable' => $reactionableResource,

        ];
    }

    /**
     * Determina el resource apropiado para el modelo reactionable
     */
    protected function determineReactionableResource(): ?JsonResource
    {
        if (!$this->relationLoaded('reactionable')) {
            return null;
        }

        // Mapeo de tipos de modelos a sus respectivos resources
        $resourceMap = [
            'App\Models\V1\Post' => PostResource::class,
            'App\Models\V1\Comment' => CommentResource::class,
            'App\Models\V1\ReplayComment' => ReplayCommentResource::class,
            // Agrega más modelos según sea necesario
        ];

        $type = $this->reactionable_type;
        $resourceClass = $resourceMap[$type] ?? null;

        return $resourceClass ? new $resourceClass($this->reactionable) : null;
    }
}
