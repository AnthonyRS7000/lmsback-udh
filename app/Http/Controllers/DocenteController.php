<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use Illuminate\Http\Request;

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
}
