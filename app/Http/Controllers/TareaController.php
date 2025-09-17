<?php

namespace App\Http\Controllers;

use App\Models\Tarea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // 🔹 para logs

class TareaController extends Controller
{
    public function index()
    {
        Log::info('Listando tareas');
        return Tarea::with(['curso', 'docente'])->get();
    }

    public function store(Request $request)
    {
        Log::info('Iniciando creación de tarea', ['request' => $request->all()]);

        try {
            $validated = $request->validate([
                'curso_id' => 'required|exists:cursos,id',
                'docente_id' => 'required|exists:docentes,id',
                'titulo' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'fecha_entrega' => 'nullable|date',
                'archivo_referencia' => 'nullable|file|max:2048',
            ]);

            Log::info('Datos validados correctamente', $validated);

            if ($request->hasFile('archivo_referencia')) {
                $path = $request->file('archivo_referencia')->store('tareas');
                $validated['archivo_referencia'] = $path;
                Log::info('Archivo subido correctamente', ['path' => $path]);
            }

            $tarea = Tarea::create($validated);

            Log::info('Tarea creada con éxito', ['tarea' => $tarea]);

            return response()->json($tarea, 201);
        } catch (\Exception $e) {
            Log::error('Error al crear tarea', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error al crear tarea',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Tarea $tarea)
    {
        Log::info('Mostrando tarea', ['tarea_id' => $tarea->id]);
        return $tarea->load(['curso', 'docente', 'entregas']);
    }

    public function update(Request $request, Tarea $tarea)
    {
        Log::info('Actualizando tarea', [
            'tarea_id' => $tarea->id,
            'request' => $request->all()
        ]);

        $tarea->update($request->all());

        Log::info('Tarea actualizada', ['tarea' => $tarea]);

        return response()->json($tarea, 200);
    }

    public function destroy(Tarea $tarea)
    {
        Log::info('Eliminando tarea', ['tarea_id' => $tarea->id]);

        $tarea->delete();

        Log::info('Tarea eliminada correctamente', ['tarea_id' => $tarea->id]);

        return response()->json(null, 204);
    }
}
