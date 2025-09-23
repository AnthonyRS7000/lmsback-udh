<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use App\Services\UdhTokenService;

class HorarioController extends Controller
{
    public function getHorario($codalu, $semsem, UdhTokenService $tokenService)
    {
        // 1. Obtener token válido desde BD
        $tokenRecord = $tokenService->getActiveToken();
        if (!$tokenRecord) {
            return response()->json(['error' => 'No hay token válido en BD'], 500);
        }
        $token = $tokenRecord->token_actual;

        // 2. URL del servicio externo
        $url = config('udh.apis.horario');

        // 3. Hacer la consulta
        $response = Http::get($url, [
            'codalu' => $codalu,
            'semsem' => $semsem,
            'token'  => $token,
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Error al consultar API UDH'], 500);
        }

        $data = $response->json();
        if (!isset($data['data']) || empty($data['data'])) {
            return response()->json(['error' => 'Sin datos en API'], 404);
        }

        return response()->json([
            'status' => 'success',
            'source' => 'udh_api',
            'data'   => $data['data'],
        ]);
    }
}
