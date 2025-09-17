<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntregaTarea extends Model
{
    use HasFactory;

    protected $table = 'entrega_tareas'; // 🔹 nombre de la tabla en plural snake_case

    protected $fillable = [
        'tarea_id',
        'estudiante_id',
        'archivo',
        'comentario',
        'nota',
        'fecha_envio',
    ];

    // 🔹 Relación con tarea
    public function tarea()
    {
        return $this->belongsTo(Tarea::class);
    }

    // 🔹 Relación con estudiante
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }
}
