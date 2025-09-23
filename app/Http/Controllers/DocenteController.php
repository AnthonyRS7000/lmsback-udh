<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
        public function getByDni($dni, UdhTokenService $tokenService)
    {
        // 1. Obtener token válido desde BD
        $tokenRecord = $tokenService->getActiveToken();
        if (!$tokenRecord) {
            return response()->json(['error' => 'No hay token válido en BD'], 500);
        }
        $token = $tokenRecord->token_actual;

        // 2. URL de la API de docentes
        $url = config('udh.apis.docentes'); 
        // 👆 en config/udh.php defines 'docentes' => 'http://www.udh.edu.pe/websauh/apis/DocentesAPI.aspx'

        // 3. Hacer la consulta
        $response = Http::get($url, [
            'action' => 'dni',
            'id'     => $dni,
            'token'  => $token,
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Error al consultar API UDH'], 500);
        }

        $data = $response->json();
        if (!isset($data['data']) || empty($data['data'])) {
            return response()->json(['error' => 'Sin datos en API', 'raw' => $data], 404);
        }

        return response()->json([
            'status' => 'success',
            'source' => 'udh_api',
            'data'   => $data['data'],
        ]);
    }
}
