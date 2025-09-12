<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Usuario extends Authenticatable
{
    use Notifiable, SoftDeletes, HasApiTokens;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombres',
        'apellidos',
        'tipo_documento',
        'numero_documento',
        'email',
        'telefono',
        'rol',            // si aún quieres mantener un rol simple
        'password',
        'google_id',
        'google_avatar',
        'provider',
        'provider_id',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Mutator: asegura que la contraseña siempre se guarde hasheada
     */
    public function setPasswordAttribute($value)
    {
        if ($value && !str_starts_with((string)$value, '$2y$')) { // evita re-hashear bcrypt
            $this->attributes['password'] = Hash::make($value);
        } else {
            $this->attributes['password'] = $value;
        }
    }

    /**
     * Relación uno a uno con Estudiante
     */
    public function estudiante(): HasOne
    {
        return $this->hasOne(Estudiante::class);
    }

    /**
     * Relación muchos a muchos con roles (via tabla pivot usuario_rol)
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'usuario_rol', 'usuario_id', 'rol_id')
                    ->withTimestamps();
    }

    /**
     * Helper para verificar si el usuario tiene un rol específico
     */
    public function hasRole(string $slug): bool
    {
        return $this->roles()->where('slug', $slug)->exists();
    }
}
