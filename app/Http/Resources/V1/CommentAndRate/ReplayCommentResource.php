<?php

namespace App\Http\Resources\V1\CommentAndRate;

use App\Http\Resources\V1\User\ImageResource;
use App\Http\Resources\V1\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class ReplayCommentResource extends JsonResource
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
            'comment' => $this->comment, // Ya aplica Str::ucfirst via accessor
            'created_at' => $this->created_at??$this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at??$this->updated_at->format('d/m/Y H:i'),

            // Relaciones principales
            'user' => new UserResource($this->whenLoaded('user')),
            'comment_parent' => new CommentResource($this->whenLoaded('comment')),

            // Relaciones polimórficas
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'reactions' => ReactionResource::collection($this->whenLoaded('reactions')),
            'complaints' => ComplaintResource::collection($this->whenLoaded('complaints')),

            'reactions_count' => $this->whenCounted('reactions'),
            'complaints_count' => $this->whenCounted('complaints'),
            'positive_reactions_count' => $this->positiveReactions()->count(),
            'negative_reactions_count' => $this->negativeReactions()->count(),

        ];
    }
}
