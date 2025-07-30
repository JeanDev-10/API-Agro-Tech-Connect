<?php

namespace App\Http\Resources\V1\User;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => Crypt::encrypt($this->id),
            'type' => $this->data['type'] ?? 'unknown',
            'message' => $this->data['message'] ?? '',
            'data'=>$this->data,
            'is_read' => $this->read_at !== null,
            'created_at' => $this->created_at??$this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at??$this->updated_at->format('d/m/Y H:i'),
            'read_at' => $this->read_at?->format('d/m/Y H:i'),
        ];
    }
}
