<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Range extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_range',
        'max_range',
        'description',
        'image_url'
    ];
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
