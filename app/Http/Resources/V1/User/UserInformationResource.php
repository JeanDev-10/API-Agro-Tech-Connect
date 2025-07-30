<?php

namespace App\Http\Resources\V1\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;
class UserInformationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */


    public function toArray(Request $request): array
    {
        return [
            'id' => Crypt::encrypt($this->id),
            'user'=>new UserResource($this->whenLoaded('user')),
            "description"=> $this->description,
		    "link1"=> $this->link1,
            "link2"=> $this->link2,
            "link3"=> $this->link3,
            'created_at' => $this->created_at??$this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at??$this->updated_at->format('d/m/Y H:i'),

        ];
    }
}
