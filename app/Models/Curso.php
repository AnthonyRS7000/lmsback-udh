<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'codigo',
        'docente_id',
    ];

    // 🔹 Relación con docente
    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }

    // 🔹 Relación con tareas
    public function tareas()
    {
        return $this->hasMany(Tarea::class);
    }
}
