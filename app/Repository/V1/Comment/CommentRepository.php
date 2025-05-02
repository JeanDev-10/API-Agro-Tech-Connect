<?php

namespace App\Repository\V1\Comment;

use App\Interfaces\V1\Comment\CommentRepositoryInterface;
use App\Models\V1\Comment;
use App\Models\V1\ReplayComment;
use App\Services\V1\ImageService;

class CommentRepository implements CommentRepositoryInterface
{
    public function __construct(
        protected ImageService $imageService,
    ) {}
    public function getReplayComments(Comment $comment)
    {
        return $comment->replies()
            ->with(['images', 'user.image'])
            ->withCount(['reactions'])
            ->latest()
            ->paginate(10);
    }
    public function show($comment)
    {
        return Comment::with(['images', 'user.image'])
            ->withCount(['reactions', 'replies'])
            ->where('id', $comment)
            ->first();
    }
    public function createCommentReply(Comment $parentComment, array $data, ?array $images = null, $user)
    {
        $replyData = [
            'comment' => $data['comment'],
            'user_id' => $user->id,
            'comment_id' => $parentComment->id
        ];

        $reply = ReplayComment::create($replyData);

        // Subir imágenes si existen
        if ($images && count($images) > 0) {
            $uploadedImages = $this->imageService->uploadImages(
                $images,
                'comments/replies/images'
            );

            foreach ($uploadedImages as $image) {
                $reply->images()->create([
                    'image_Uuid' => $image['path'],
                    'url' => $image['url'],
                ]);
            }
        }

        return $reply->load('user.image', 'images');
    }
}
