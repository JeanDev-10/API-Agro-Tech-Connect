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
        return $this->belongsTo(User::class);
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
}
