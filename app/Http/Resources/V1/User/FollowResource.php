<?php

namespace App\Http\Resources\V1\User;

use App\Http\Resources\V1\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class FollowResource extends JsonResource
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
            'follower_id' => Crypt::encrypt($this->follower_id),
            'followed_id' => Crypt::encrypt($this->followed_id),
            'follower' => new UserResource($this->whenLoaded('follower')),
            'followed' => new UserResource($this->whenLoaded('followed')),
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i'),
        ];
    }
}
