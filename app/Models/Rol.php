<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rol extends Model
{
    use SoftDeletes;

    protected $table = 'roles';

    protected $fillable = [
        'nombre', 'slug', 'descripcion',
    ];

    public function usuarios(): BelongsToMany
    {
        // tabla pivot: usuario_rol (no plural irregular)
        return $this->belongsToMany(Usuario::class, 'usuario_rol', 'rol_id', 'usuario_id')
                    ->withTimestamps();
    }
}
