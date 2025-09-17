<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    public function index()
    {
        return Curso::with('docente')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'codigo' => 'required|string|max:50|unique:cursos,codigo',
            'docente_id' => 'required|exists:docentes,id',
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
}
