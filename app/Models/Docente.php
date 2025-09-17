<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombres',
        'apellidos',
        'email',
        'telefono',
    ];

    // 🔹 Relación con cursos
    public function cursos()
    {
        return $this->hasMany(Curso::class);
    }

    // 🔹 Relación con tareas
    public function tareas()
    {
        return $this->hasMany(Tarea::class);
    }
}
