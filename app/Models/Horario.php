<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $fillable = [
        'carga_id',
        'dia',
        'hora_inicio',
        'hora_fin',
        'aula',
        'modalidad',
    ];

    public function carga()
    {
        return $this->belongsTo(CargaAcademica::class);
    }
}
