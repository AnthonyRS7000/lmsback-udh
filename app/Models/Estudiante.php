<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Estudiante extends Model
{
    use SoftDeletes;

    protected $table = 'estudiantes';

    protected $fillable = [
        'usuario_id',
        'escuela_id',
        'codigo',
        'fecha_ingreso',
        'estado',
    ];

    /**
     * Relación inversa hacia Usuario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /**
     * Relación hacia Escuela (si existe la tabla escuelas)
     */
    public function escuela(): BelongsTo
    {
        return $this->belongsTo(Escuela::class);
    }
}
