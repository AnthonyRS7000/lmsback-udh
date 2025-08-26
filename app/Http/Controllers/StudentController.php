<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    // Listar todos los estudiantes
    public function index()
    {
        return Student::all();
    }

    // Crear nuevo estudiante
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

    // Mostrar un estudiante por ID
    public function show(Student $student)
    {
        return $student;
    }

    // Actualizar estudiante
    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:students,email,' . $student->id,
            'dni'   => 'sometimes|string|max:20|unique:students,dni,' . $student->id,
        ]);

        $student->update($validated);

        return response()->json($student);
    }

    // Eliminar estudiante
    public function destroy(Student $student)
    {
        $student->delete();

        return response()->json(null, 204);
    }
}
