<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Comment extends Model
{
    /** @use HasFactory<\Database\Factories\CommentFactory> */
    use HasFactory;
    protected $fillable = [
        'comment',
        'post_id',
        'user_id'
    ];

    protected static function booted()
    {
        static::deleting(function ($comment) {
            // Eliminar todas las respuestas al comentario
            $comment->replies()->delete();

            // Eliminar todas las imágenes asociadas al comentario
            if($comment->images()->exists()) {
                $comment->images()->delete();
            }
        });
    }
    public function setCommentAttribute($value)
    {
        $this->attributes['comment'] = mb_strtolower($value, 'UTF-8');
    }

    public function getCommentAttribute($value)
    {
        return Str::ucfirst($value);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class)->withDefault([
            'title' => 'Publicación eliminada',
            'description' => 'Esta publicación ha sido eliminada'
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault([
            'name' => 'Usuario eliminado',
            'email' => 'deleted@example.com',
            'username' => 'deleted_user'
        ]);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ReplayComment::class);
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactionable');
    }
    public function complaints(): MorphMany
    {
        return $this->morphMany(Complaint::class, 'complaintable');
    }
}
