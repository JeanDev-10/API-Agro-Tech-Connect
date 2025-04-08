<?php

namespace App\Http\Resources\V1\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;
class LoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

     public function __construct($user, private $token = null)
    {
        parent::__construct($user);
        $this->token = $token;
    }
    public function toArray(Request $request): array
    {
        return [
            'id' => Crypt::encrypt($this->id),
            'name' => $this->name,
            'lastname' => $this->lastname,
            'username' => $this->username,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'registration_method' => $this->registration_method,
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i'),
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'created_at' => $role->created_at->format('d/m/Y H:i'),
                        'updated_at' => $role->updated_at->format('d/m/Y H:i'),
                    ];
                });
            }),
            'token' => $this->when($this->token, $this->token)
        ];
    }
}
