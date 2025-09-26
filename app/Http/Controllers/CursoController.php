<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\UdhTokenService;

class CursoController extends Controller
{
    public function index()
    {
        return Curso::with('docente')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'      => 'required|string|max:150',
            'codigo'      => 'required|string|max:50|unique:cursos,codigo',
            'docente_id'  => 'required|exists:docentes,id',
        ]);

        $curso = Curso::create($validated);

        return response()->json($curso, 201);
    }

    public function show(Curso $curso)
    {
        return $curso->load(['docente', 'tareas']);
    }

    public function update(Request $request, Curso $curso)
    {
        $curso->update($request->all());
        return response()->json($curso, 200);
    }

    public function destroy(Curso $curso)
    {
        $curso->delete();
        return response()->json(null, 204);
    }

    /**
     * Obtener cursos llevados por un estudiante desde API UDH
     * GET /api/estudiantes/cursos-llevados?codalu=XXXX
     */
    public function cursosLlevados(Request $request, UdhTokenService $tokenService)
    {
        $request->validate([
            'codalu' => 'required|string|max:20',
        ]);

        $codalu = trim($request->input('codalu'));

        Log::info('📌 cursosLlevados() - Parámetros recibidos', compact('codalu'));

        try {
            // 1. Verificar estudiante en BD
            $estudiante = Estudiante::where('codigo', $codalu)->first();
            if (!$estudiante) {
                return response()->json(['error' => 'Estudiante no encontrado'], 404);
            }

            // 2. Obtener token válido
            $tokenRecord = $tokenService->getActiveToken();
            if (!$tokenRecord) {
                return response()->json(['error' => 'No hay token válido en BD'], 500);
            }
            $token = $tokenRecord->token_actual;

            // 3. Construir URL de la API
            $url = config('udh.apis.cursos_llevados');
            $query = [
                'codalu' => $codalu,
                'token'  => $token,
            ];

            Log::info('🌍 Consultando API UDH de CURSOS LLEVADOS', ['url' => $url, 'query' => $query]);

            // 4. Llamar a la API
            $response = Http::get($url, $query);

            Log::info('📡 Respuesta API UDH (cursos llevados)', [
                'status'       => $response->status(),
                'body_preview' => substr($response->body(), 0, 300),
            ]);

            if ($response->failed()) {
                return response()->json(['error' => 'Error al consultar API UDH'], 500);
            }

            $json = $response->json();
            $payload = $json['data'] ?? $json;

            return response()->json([
                'status'     => 'success',
                'estudiante' => $estudiante,
                'data'       => $payload,
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ Error en cursosLlevados()', ['msg' => $e->getMessage()]);
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }
}
