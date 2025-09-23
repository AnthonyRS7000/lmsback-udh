<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\UdhTokenService;

class DocenteController extends Controller
{
    public function index()
    {
        return Docente::with('cursos')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombres' => 'required|string|max:150',
            'apellidos' => 'required|string|max:150',
            'email' => 'required|email|unique:docentes,email',
            'telefono' => 'nullable|string|max:20',
        ]);

        $docente = Docente::create($validated);

        return response()->json($docente, 201);
    }

    public function show(Docente $docente)
    {
        return $docente->load('cursos');
    }

    public function update(Request $request, Docente $docente)
    {
        $docente->update($request->all());
        return response()->json($docente, 200);
    }

    public function destroy(Docente $docente)
    {
        $docente->delete();
        return response()->json(null, 204);
    }

    public function getByDni($dni, UdhTokenService $tokenService, Request $request)
    {
        // 🔒 Verificar ability del token
        if (! $request->user()->tokenCan('docentes:read')) {
            Log::warning("🚫 Token sin permiso para docentes:read", [
                'user_id' => $request->user()->id,
                'email'   => $request->user()->email,
            ]);
            return response()->json(['error' => 'No autorizado'], 403);
        }

        Log::info("📌 Consultando API UDH para docente", ['dni' => $dni]);

        // 1. Obtener token válido desde BD
        $tokenRecord = $tokenService->getActiveToken();
        if (!$tokenRecord) {
            Log::error("❌ No hay token válido en BD");
            return response()->json(['error' => 'No hay token válido en BD'], 500);
        }
        $token = $tokenRecord->token_actual;

        Log::info("🔑 Token usado para API UDH");

        // 2. URL de la API de docentes
        $url = config('udh.apis.docentes');
        Log::info("🌍 URL API", ['url' => $url]);

        // 3. Hacer la consulta
        $response = Http::get($url, [
            'action' => 'dni',
            'id'     => $dni,
            'token'  => $token,
        ]);

        Log::info("📡 Respuesta API UDH", [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        if ($response->failed()) {
            Log::error("❌ Error al consultar API UDH", ['status' => $response->status()]);
            return response()->json(['error' => 'Error al consultar API UDH'], 500);
        }

        $data = $response->json();

        // 👇 la API puede devolver directamente un objeto o dentro de "data"
        $result = $data['data'] ?? $data;

        if (empty($result)) {
            Log::warning("⚠️ API UDH devolvió vacío", ['raw' => $data]);
            return response()->json(['error' => 'Sin datos en API', 'raw' => $data], 404);
        }

        Log::info("✅ Docente obtenido correctamente", ['dni' => $dni]);

        return response()->json([
            'status' => 'success',
            'source' => 'udh_api',
            'data'   => $result,
        ]);
    }
}
