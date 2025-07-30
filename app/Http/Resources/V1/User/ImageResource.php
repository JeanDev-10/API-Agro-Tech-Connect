<?php

namespace App\Http\Resources\V1\User;

use App\Http\Resources\V1\CommentAndRate\CommentResource;
use App\Http\Resources\V1\CommentAndRate\ReplayCommentResource;
use App\Http\Resources\V1\Post\PostResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class ImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        // Determina el resource adecuado para el modelo relacionado
        $imageableResource = $this->determineImageableResource();

        return [
            'id' => Crypt::encrypt($this->id),
            'image_Uuid' => $this->image_Uuid,
            'url' => $this->url,
            'imageable_type' => $this->imageable_type,
            'imageable' => $imageableResource,
            'created_at' => $this->created_at??$this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at??$this->updated_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Determina el resource apropiado para el modelo imageable
     *
     * @return JsonResource|null
     */
    protected function determineImageableResource(): ?JsonResource
    {
        if (!$this->relationLoaded('imageable')) {
            return null;
        }

        if (!$this->imageable) {
            return null;
        }

        // Mapeo de tipos de modelos a sus respectivos resources
        $resourceMap = [
            'App\Models\V1\Post' => PostResource::class,
            'App\Models\V1\User' => UserResource::class,
            'App\Models\V1\Comment' => CommentResource::class,
            'App\Models\V1\ReplayComment' => ReplayCommentResource::class,
            // Agrega más mapeos según sea necesario
        ];

        $type = $this->imageable_type;
        $resourceClass = $resourceMap[$type] ?? null;

        return $resourceClass ? new $resourceClass($this->imageable) : null;
    }
}
