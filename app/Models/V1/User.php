<?php

namespace App\Models\V1;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'lastname',
        'username',
        'email',
        'password',
        'registration_method',
        'email_verified_at',
        'firebase_Uuid'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'firebase_Uuid',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime:d/m/Y H:i', // Formato personalizado
        'updated_at' => 'datetime:d/m/Y H:i',
    ];

    /**
     * Mutators para convertir a minúsculas antes de guardar
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = mb_strtolower($value, 'UTF-8');
    }

    public function setLastnameAttribute($value)
    {
        $this->attributes['lastname'] = mb_strtolower($value, 'UTF-8');
    }

    public function setUsernameAttribute($value)
    {
        $this->attributes['username'] = mb_strtolower($value, 'UTF-8');
    }

    /**
     * Accessors para mostrar los datos formateados
     * (Opcional: si quieres capitalizar al recuperarlos)
     */
    public function getNameAttribute($value)
    {
        return ucwords($value); // Convierte "juan pérez" a "Juan Pérez"
    }

    public function getLastnameAttribute($value)
    {
        return ucwords($value);
    }



    // Método para guardar password antiguo
    public function saveOldPassword()
    {
        $this->oldPasswords()->create([
            'password' => $this->password
        ]);
    }



    public function oldPasswords()
    {
        return $this->hasMany(OldPassword::class);
    }
}
