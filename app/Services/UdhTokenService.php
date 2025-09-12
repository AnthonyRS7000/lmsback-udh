<?php

namespace App\Services;

use App\Models\TokenUdh;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class UdhTokenService
{
    protected $url = 'http://www.udh.edu.pe/websauh/apis/TokenGenerator.aspx';

    /**
     * Consulta la API y guarda un nuevo token en BD
     */

    public function refreshToken()
    {
        $response = Http::get($this->url);

        if ($response->failed()) {
            throw new \Exception('No se pudo obtener token de la API UDH');
        }

        $data = $response->json();

        // Restar 5 horas a la fecha que manda el inge (corregir su desfase)
        $expiresAt = Carbon::parse($data['token_valido_hasta'])->subHours(5);

        TokenUdh::create([
            'token_actual'  => $data['token_actual'],
            'valido_hasta'  => $expiresAt,
            'token_proximo' => $data['token_proximo'] ?? null,
        ]);
    }


    /**
     * Obtiene el último token válido de la BD
     */
    public function getActiveToken()
    {
        return TokenUdh::latest('id')->first();
    }
}
