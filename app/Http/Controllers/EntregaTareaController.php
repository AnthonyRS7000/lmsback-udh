<?php

namespace App\Http\Controllers;

use App\Models\EntregaTarea;
use Illuminate\Http\Request;

class EntregaTareaController extends Controller
{
    public function index()
    {
        return EntregaTarea::with(['tarea', 'estudiante'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tarea_id' => 'required|exists:tareas,id',
            'estudiante_id' => 'required|exists:estudiantes,id',
            'archivo' => 'nullable|file|max:4096',
            'comentario' => 'nullable|string',
        ]);

        if ($request->hasFile('archivo')) {
            $validated['archivo'] = $request->file('archivo')->store('entregas');
        }

        $entrega = EntregaTarea::create($validated);

        return response()->json($entrega, 201);
    }

    public function show(EntregaTarea $entregaTarea)
    {
        return $entregaTarea->load(['tarea', 'estudiante']);
    }

    public function update(Request $request, EntregaTarea $entregaTarea)
    {
        $entregaTarea->update($request->all());
        return response()->json($entregaTarea, 200);
    }

    public function destroy(EntregaTarea $entregaTarea)
    {
        $entregaTarea->delete();
        return response()->json(null, 204);
    }
}
