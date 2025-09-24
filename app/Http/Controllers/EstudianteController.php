<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\UdhTokenService;

class EstudianteController extends Controller
{
    /**
     * Obtener notas del estudiante desde API UDH.
     * GET /api/estudiantes/notas?codalu=XXXX&semsem=YYYY-Z
     */
    public function verNotas(Request $request, UdhTokenService $tokenService)
    {
        // 1) Validación
        $request->validate([
            'codalu' => 'required|string|max:20',
            'semsem' => 'required|string|max:10',
        ]);

        // Normaliza y LOG de lo que realmente llegó
        $codalu = trim((string) $request->input('codalu'));
        $semsem = trim((string) $request->input('semsem'));

        Log::info('📌 verNotas() - Parámetros recibidos', compact('codalu','semsem'));

        try {
            // 2) Verifica estudiante en BD
            $estudiante = Estudiante::where('codigo', $codalu)->first();
            if (!$estudiante) {
                Log::warning('⚠️ Estudiante no encontrado', compact('codalu'));
                return response()->json(['error' => 'Estudiante no encontrado'], 404);
            }

            // 3) Token vigente
            $tokenRecord = $tokenService->getActiveToken();
            if (!$tokenRecord) {
                Log::error('❌ No hay token válido en BD');
                return response()->json(['error' => 'No hay token válido en BD'], 500);
            }
            $token = $tokenRecord->token_actual;

            // 4) URL correcta del endpoint de NOTAS
            $url = config('udh.apis.estudiante_notas');

            // Sanity check: si por error apunta a Horario, avisa
            if (str_contains($url, 'HorarioApi.aspx')) {
                Log::error('❌ Config mal apuntada: estudiante_notas está usando HorarioApi.aspx', ['url' => $url]);
                return response()->json(['error' => 'Config de API de notas mal configurada'], 500);
            }

            $query = [
                'codalu' => $codalu,
                'semsem' => $semsem,
                'token'  => $token,
            ];

            Log::info('🌍 Consultando API UDH de NOTAS', ['url' => $url, 'query' => $query]);

            // 5) Request a la API externa (sin forzar acceptJson)
            $response = Http::get($url, $query);

            Log::info('📡 Respuesta API UDH (notas)', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body_preview' => substr($response->body(), 0, 300) // solo primeras 300 chars para debug
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Error al consultar API UDH',
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ], 500);
            }

            $body = $response->body();

            // 6) Validar si realmente es JSON
            if ($this->isJson($body)) {
                $json = $response->json();
                $payload = $json['data'] ?? $json;

                return response()->json([
                    'status'     => 'success',
                    'estudiante' => $estudiante,
                    'data'       => $payload,
                ], 200);
            }

            // 7) Si la API devolvió HTML u otro formato
            Log::warning('⚠️ API devolvió HTML en lugar de JSON', ['body' => $body]);

            return response()->json([
                'status'  => 'error',
                'message' => 'La API UDH devolvió HTML en lugar de JSON',
                'body'    => $body,
            ], 502);

        } catch (\Throwable $e) {
            Log::error('❌ Error en verNotas()', ['msg' => $e->getMessage()]);
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Verifica si un string es JSON válido.
     */
    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }
}
