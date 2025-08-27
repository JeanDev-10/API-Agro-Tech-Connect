<?php

namespace App\Models\V1;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\V1\CustomResetPassword;
use App\Notifications\V1\CustomVerifyEmail;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail, CanResetPassword
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



    protected static function booted()
    {
        static::deleting(function ($user) {
            // Solo eliminar la imagen del usuario, no los posts
            if ($user->image()->exists()) {
                $user->image()->delete();
            }

            // Actualizar los posts para establecer user_id como null
            $user->posts()->update(['user_id' => null]);

            // Actualizar comentarios y respuestas para establecer user_id como null
            $user->comments()->update(['user_id' => null]);
            $user->replayComments()->update(['user_id' => null]);

            // Actualizar reacciones para establecer user_id como null
            $user->reactions()->update(['user_id' => null]);
            $user->complaints()->update(['user_id' => null]); // Mantener denuncias pero sin usuario
        });
    }

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
    public function getUserNameAttribute($value)
    {
        return ucwords($value);
    }



    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPassword($token));
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
    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    /**
     * Seguimientos donde este usuario es el seguidor
     */
    public function followings()
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }

    /**
     * Seguimientos donde este usuario es el seguido
     */
    public function followers()
    {
        return $this->hasMany(Follow::class, 'followed_id');
    }


    /**
     * Verificar si el usuario sigue a otro
     */
    public function isFollowing(User $user): bool
    {
        return $this->followings()->where('followed_id', $user->id)->exists();
    }

    /**
     * Verificar si el usuario es seguido por otro
     */
    public function isFollowedBy(User $user): bool
    {
        return $this->followers()->where('follower_id', $user->id)->exists();
    }

    public function ranges()
    {
        return $this->belongsToMany(Range::class)
            ->withTimestamps()
            ->withPivot('achieved_at')
            ->orderByDesc('ranges.min_range'); // Ordenar por rango más alto
    }

    public function getCurrentRangeAttribute()
    {
        return $this->ranges()->orderByDesc('ranges.min_range')->first();
    }

    public function hasRange(Range $range): bool
    {
        return $this->ranges()->where('range_id', $range->id)->exists();
    }
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function replayComments()
    {
        return $this->hasMany(ReplayComment::class);
    }
    public function userInformation()
    {
        return $this->hasOne(UserInformation::class);
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }
    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }
}
