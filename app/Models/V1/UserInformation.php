<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
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
     * Mutator: Guarda el texto en minúsculas pero preserva emojis
     */
    public function setDescriptionAttribute($value)
    {
        if ($value !== null) {
            // Convertir a minúsculas solo los caracteres alfabéticos, preservando emojis y otros caracteres
            $this->attributes['description'] = mb_strtolower($value, 'UTF-8');
        } else {
            $this->attributes['description'] = null;
        }
    }

    /**
     * Accessor: Devuelve la primera letra en mayúscula
     */
    public function getDescriptionAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        // Capitalizar la primera letra de la cadena, preservando emojis
        return Str::ucfirst($value);
    }
}
