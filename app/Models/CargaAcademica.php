<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargaAcademica extends Model
{
    protected $fillable = [
        'codper',
        'semsem',
        'seccion',
        'curso_id',
        'docente_id',
    ];

    /**
     * Relación con curso
     */
    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    /**
     * Relación con docente (apunta a usuarios)
     */
    public function docente()
    {
        return $this->belongsTo(Usuario::class, 'docente_id');
    }

    /**
     * Relación con horarios
     */
    public function horarios()
    {
        return $this->hasMany(Horario::class, 'carga_id');
    }

    /**
     * Relación con matrículas (muchos a muchos vía tabla pivote matricula_detalles)
     */
    public function matriculas()
    {
        return $this->belongsToMany(
            Matricula::class,
            'matricula_detalles', // tabla pivote
            'carga_id',           // FK en la pivote que apunta a carga_academicas
            'matricula_id'        // FK en la pivote que apunta a matriculas
        )->withTimestamps();
    }
}
