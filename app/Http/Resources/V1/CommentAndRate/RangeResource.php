<?php

namespace App\Http\Resources\V1\CommentAndRate;

use App\Http\Resources\V1\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class RangeResource extends JsonResource
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
            'name' => $this->name,
            'min_range' => $this->min_range,
            'max_range' => $this->max_range,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'created_at' => $this->created_at??$this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at??$this->updated_at->format('d/m/Y H:i'),

            // Relaciones
            'users' => UserResource::collection($this->whenLoaded('users')),
        ];
    }
}
