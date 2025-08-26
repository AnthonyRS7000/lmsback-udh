<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Listar todos los estudiantes
     */
    public function index()
    {
        return response()->json(Student::all(), 200);
    }

    /**
     * Crear nuevo estudiante
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:students',
            'dni'   => 'required|string|max:20|unique:students',
        ]);

        $student = Student::create($validated);

        return response()->json($student, 201);
    }

    /**
     * Mostrar un estudiante por ID
     */
    public function show($id)
    {
        $student = Student::findOrFail($id);
        return response()->json($student, 200);
    }

    /**
     * Actualizar estudiante
     */
    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:students,email,' . $student->id,
            'dni'   => 'sometimes|string|max:20|unique:students,dni,' . $student->id,
        ]);

        $student->update($validated);

        return response()->json($student, 200);
    }

    /**
     * Eliminar estudiante
     */
    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();

        return response()->json(null, 204);
    }
}
