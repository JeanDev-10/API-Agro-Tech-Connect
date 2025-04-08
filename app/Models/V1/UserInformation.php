<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInformation extends Model
{
    protected $table = 'user_informations';
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'description',
        'link1',
        'link2',
        'link3',
        'user_id'
    ];

    /**
     * Get the user that owns the information.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Set the description with emoji support.
     */
    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = $value;
    }
}
