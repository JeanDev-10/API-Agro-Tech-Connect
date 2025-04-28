<?php
namespace App\Interfaces\V1\Post;


interface PostRepositoryInterface{
    public function index($filters);
    public function show($id);
}
