<?php
namespace App\Interfaces\V1\Post;

use App\Models\V1\Post;

interface PostRepositoryInterface{
    public function index($filters);
    public function show($id);
    public function createPostWithImages(array $data, $images = null);
    public function attachImagesToPost(Post $post, array $images);
    public function notifyFollowers(Post $post);
    public function updatePostWithImages(Post $post, array $data, $images = null);
    public function deleteOldImages(Post $post);

}
