<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    /** @use HasFactory<\Database\Factories\FollowFactory> */
    use HasFactory;
    protected $fillable = [
        'follower_id',
        'followed_id'
    ];

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    /**
     * Usuario que es seguido
     */
    public function followed()
    {
        return $this->belongsTo(User::class, 'followed_id');
    }
}
