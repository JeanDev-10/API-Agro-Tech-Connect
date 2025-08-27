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


        return [
            'id' => Crypt::encrypt($this->id),
            'type' => $this->type,
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i'),

            // Relación con usuario
            'user' => new UserResource($this->whenLoaded('user')),

            // Relación polimórfica
            'reactionable_type' => class_basename($this->reactionable_type), // Solo el nombre de la clase

        ];
    }
}
