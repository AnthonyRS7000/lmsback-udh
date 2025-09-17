<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'role_id',          // 👈 en lugar de "rol"
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
     * Relación hacia Rol (un usuario pertenece a un rol)
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'role_id');
    }

    /**
     * Helper para verificar si el usuario tiene un rol específico
     */
    public function hasRole(string $slug): bool
    {
        return $this->role && $this->role->slug === $slug;
    }
}
