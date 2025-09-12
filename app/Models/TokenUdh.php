<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenUdh extends Model
{
    protected $table = 'tokensudh';

    protected $fillable = [
        'token_actual',
        'valido_hasta',
        'token_proximo',
    ];

    protected $dates = [
        'valido_hasta',
    ];
}
