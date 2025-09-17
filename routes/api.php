<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\TokenUdhController;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\EntregaTareaController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\CursoController;

// === Autenticación con Google ===
Route::get('/auth/google', [UsuarioController::class, 'redirectToGoogle']);   // Paso 1: redirección
Route::get('/auth/google/callback', [UsuarioController::class, 'handleGoogleCallback']); // Paso 2: callback

// === Token del sistema antiguo (ejemplo) ===
Route::get('/udh/token', [TokenUdhController::class, 'token']);
    Route::apiResource('tareas', TareaController::class);

// === Rutas protegidas con Sanctum ===
Route::middleware('auth:sanctum')->group(function () {

    // === Usuarios ===
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::get('/usuarios/{usuario}', [UsuarioController::class, 'show']);

    // === Tareas ===


    // === Entregas de Tareas ===
    Route::apiResource('entregas', EntregaTareaController::class);

    // === Docentes ===
    Route::apiResource('docentes', DocenteController::class);

    // === Cursos ===
    Route::apiResource('cursos', CursoController::class);
});
