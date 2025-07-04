<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class ReplayComment extends Model
{
    protected $table = "replay_comments";
    /** @use HasFactory<\Database\Factories\ReplayCommentFactory> */
    use HasFactory;

    protected $fillable = [
        'comment',
        'comment_id',
        'user_id'
    ];


    protected static function booted()
    {
        static::deleting(function ($replayComment) {
            // Eliminar todas las imágenes asociadas a la respuesta
            if($replayComment->images()->exists()) {
                $replayComment->images()->delete();
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

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class)->withDefault([
            'comment' => 'Comentario eliminado'
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
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactionable');
    }
    public function complaints(): MorphMany
    {
        return $this->morphMany(Complaint::class, 'complaintable');
    }
    public function positiveReactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactionable')
            ->where('type', 'positivo')
            ->with('user.image'); // Cargar siempre el usuario
    }

    public function negativeReactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactionable')
            ->where('type', 'negativo')
            ->with('user.image'); // Cargar siempre el usuario
    }
}
