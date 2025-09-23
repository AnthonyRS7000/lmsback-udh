<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'ciclo',
        'creditos',
        'docente_id',
    ];

    // Un curso puede tener varias cargas académicas
    public function cargas()
    {
        return $this->hasMany(CargaAcademica    ::class);
    }

    // Docente responsable principal (si aplica)
    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }
}
