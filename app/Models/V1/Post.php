<?php

namespace App\Models\V1;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'user_id'
    ];
    protected static function booted()
    {
        static::deleting(function ($post) {
            // Eliminar todas las imágenes asociadas al post
            if ($post->images()->exists()) {
                $post->images()->delete();
            }
            // Eliminar todos los comentarios (que a su vez eliminarán sus respuestas e imágenes)
            $post->comments()->delete();

            // Eliminar todas las reacciones asociadas al post
            $post->reactions()->delete();
        });
    }

    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = mb_strtolower($value, 'UTF-8');
    }

    public function getTitleAttribute($value)
    {
        return Str::ucfirst($value);
    }

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = mb_strtolower($value, 'UTF-8');
    }

    public function getDescriptionAttribute($value)
    {
        return Str::ucfirst($value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault([
            'name' => 'Usuario eliminado',
            'email' => 'deleted@example.com',
            'username' => 'deleted_user'
        ]);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
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
