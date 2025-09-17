<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarea extends Model
{
    use HasFactory;

    protected $fillable = [
        'curso_id',
        'docente_id',
        'titulo',
        'descripcion',
        'fecha_entrega',
        'archivo_referencia',
        'estado',
    ];

    // 🔹 Relación con curso
    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    // 🔹 Relación con docente
    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }

    // 🔹 Relación con entregas
    public function entregas()
    {
        return $this->hasMany(EntregaTarea::class);
    }

}
