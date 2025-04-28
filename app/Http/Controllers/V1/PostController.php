<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Post\PostResource;
use App\Http\Responses\V1\ApiResponse;
use App\Models\V1\Post;
use App\Repository\V1\Post\PostRepository;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(private PostRepository $postRepository) {}

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['year', 'month', 'search']);
            $posts = $this->postRepository->index($filters);
            if($posts->isEmpty()) {
                return ApiResponse::error("No se encontraron posts", 404);
            }
            return ApiResponse::success(
                'Listado de Posts',
                200,
                $posts->through(function ($post) {
                    return new PostResource($post);
                })
            );
        } catch (\Exception $e) {
            return ApiResponse::error("Ha ocurrido un error" . $e->getMessage(), 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        //
    }
}
