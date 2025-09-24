<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\TokenUdhController;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\EntregaTareaController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\EstudianteController;

// === Autenticación con Google ===
Route::get('/auth/google', [UsuarioController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [UsuarioController::class, 'handleGoogleCallback']);
Route::post('/login', [UsuarioController::class, 'login']);

// === Token del sistema antiguo (ejemplo) ===
Route::get('/udh/token', [TokenUdhController::class, 'token']);

// === Rutas públicas (ejemplo: tareas)
Route::apiResource('tareas', TareaController::class);

// === Rutas protegidas con Sanctum ===
Route::middleware('auth:sanctum')->group(function () {

    // === Usuarios ===
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::get('/usuarios/{usuario}', [UsuarioController::class, 'show']);

    //ESTUDIANTES
    Route::get('/estudiantes/notas', [EstudianteController::class, 'verNotas']);

    // === Horarios ===
    Route::get('/horario/{codalu}/{semsem}', [HorarioController::class, 'getHorario']);

    // === Entregas de Tareas ===
    Route::apiResource('entregas', EntregaTareaController::class);

    // === Docentes (uso general) ===
    Route::apiResource('docentes', DocenteController::class);

    // === Docentes (uso técnico con token especial) ===
    Route::get('/docentes/dni/{dni}', [DocenteController::class, 'getByDni']);
    
    // === Cursos ===
    Route::apiResource('cursos', CursoController::class);
});
