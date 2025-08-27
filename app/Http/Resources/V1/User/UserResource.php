<?php

namespace App\Http\Resources\V1\User;

use App\Http\Resources\V1\CommentAndRate\ComplaintResource;
use App\Http\Resources\V1\CommentAndRate\RangeResource;
use App\Http\Resources\V1\CommentAndRate\ReactionResource;
use App\Http\Resources\V1\Post\PostResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $response = [
            'id' => Crypt::encrypt($this->id),
            'name' => $this->name,
            'lastname' => $this->lastname,
            'username' => $this->username,
            'email' => $this->email,
            'registration_method' => $this->registration_method,
            'email_verified' => (bool) $this->email_verified_at,
            'created_at' => $this->created_at ? $this->created_at->format('d/m/Y H:i'):null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('d/m/Y H:i'):null,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'created_at' => $role->created_at ? $role->created_at->format('d/m/Y H:i'):null,
                        'updated_at' => $role->updated_at ? $role->updated_at->format('d/m/Y H:i'):null,
                    ];
                });
            }),
            // Relaciones principales con eager loading
            'image' => new ImageResource($this->whenLoaded('image')),
            'user_information' => new UserInformationResource($this->whenLoaded('userInformation')),
            'ranges' => RangeResource::collection($this->whenLoaded('ranges')),
            // Counts para relaciones importantes
            'followers_count' => $this->whenCounted('followers'),
            'following_count' => $this->whenCounted('followings'),
            'posts_count' => $this->whenCounted('posts'),
            'comments_count' => $this->whenCounted('comments'),

            // Relaciones completas (cargar solo cuando se solicitan)
            'followers' => FollowResource::collection($this->whenLoaded('followers')),

            'followings' => FollowResource::collection($this->whenLoaded('followings')),

            'reactions' => ReactionResource::collection($this->whenLoaded('reactions', function () {
                return $this->reactions()->with(['user.image', 'reactionable'])->get();
            })),

            'complaints' => ComplaintResource::collection($this->whenLoaded('complaints', function () {
                return $this->complaints()->with(['user.image', 'complaintable'])->get();
            })),

            'posts' => PostResource::collection($this->whenLoaded('posts', function () {
                return $this->posts()
                    ->withCount(['comments', 'reactions'])
                    ->latest()
                    ->paginate(10, ['*'], 'posts_page');
            })),
        ];

        // Incluir paginación si está cargada
        if ($this->relationLoaded('posts') && method_exists($this->posts, 'currentPage')) {
            $response['posts_meta'] = [
                'current_page' => $this->posts->currentPage(),
                'total_pages' => $this->posts->lastPage(),
                'per_page' => $this->posts->perPage(),
                'total' => $this->posts->total(),
            ];
        }

        return $response;
    }
}
