<?php

namespace App\Http\Resources\V1\CommentAndRate;

use App\Http\Resources\V1\Post\PostResource;
use App\Http\Resources\V1\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class ComplaintResource extends JsonResource
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
        $complaintableResource = $this->determineComplaintableResource();

        return [
            'id' => Crypt::encrypt($this->id),
            'description' => $this->description,
            'user' => new UserResource($this->whenLoaded('user')),
            'complaintable_type' => $this->complaintable_type,
            'complaintable' => $complaintableResource,
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Determina el resource apropiado para el modelo complaintable
     *
     * @return mixed
     */
    protected function determineComplaintableResource()
    {
        if (!$this->relationLoaded('complaintable')) {
            return null;
        }

        // Mapeo de tipos a resources
        $resourceMap = [
            'App\Models\V1\Post' => PostResource::class,
            'App\Models\V1\Comment' => CommentResource::class,
            'App\Models\V1\ReplayComment' => ReplayCommentResource::class,
            // Agrega más mapeos según tus necesidades
        ];

        $type = $this->complaintable_type;
        $resourceClass = $resourceMap[$type] ?? null;

        return $resourceClass ? new $resourceClass($this->complaintable) : null;
    }
}
