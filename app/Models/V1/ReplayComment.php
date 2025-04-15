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
        return $this->belongsTo(Comment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
