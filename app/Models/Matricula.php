<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matricula extends Model
{
    protected $fillable = [
        'estudiante_id',
        'semsem',
        'fecha_matricula',
    ];

    // Relación con estudiante
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    // Relación con cargas académicas (muchos a muchos vía detalles)
    public function cargas()
    {
        return $this->belongsToMany(
            CargaAcademica::class,
            'matricula_detalles', // tabla pivote
            'matricula_id',       // FK en la pivote que apunta a matriculas
            'carga_id'            // FK en la pivote que apunta a carga_academicas
        )->withTimestamps();
    }



    // Relación con detalles
    public function detalles()
    {
        return $this->hasMany(MatriculaDetalle::class);
    }
}
