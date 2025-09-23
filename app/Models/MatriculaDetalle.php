<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatriculaDetalle extends Model
{
    protected $table = 'matricula_detalles';

    protected $fillable = [
        'matricula_id',
        'carga_id',
    ];

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function carga()
    {
        return $this->belongsTo(CargaAcademica::class);
    }
}
