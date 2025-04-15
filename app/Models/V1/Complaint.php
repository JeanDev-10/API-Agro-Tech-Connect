<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'user_id',
        'complaintable_id',
        'complaintable_type'
    ];

    /**
     * Mutator: Guarda el texto en minúsculas pero preserva emojis
     */
    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = mb_strtolower($value, 'UTF-8');
    }

    /**
     * Accessor: Devuelve la primera letra en mayúscula
     */
    public function getDescriptionAttribute($value)
    {
        return Str::ucfirst($value);
    }

    /**
     * Relación polimórfica
     */
    public function complaintable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Usuario que realiza la denuncia
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
