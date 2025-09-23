<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\TokenUdhController;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\EntregaTareaController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\HorarioController;

// =========================
//  Autenticación pública
// =========================
Route::get('/auth/google', [UsuarioController::class, 'redirectToGoogle']);   // Paso 1: redirección
Route::get('/auth/google/callback', [UsuarioController::class, 'handleGoogleCallback']);
Route::post('/login', [UsuarioController::class, 'login']);

// =========================
//  Token del sistema antiguo
// =========================
Route::get('/udh/token', [TokenUdhController::class, 'token']);

// =========================
//  Rutas protegidas con Sanctum (usuarios normales)
// =========================
Route::middleware('auth:sanctum')->group(function () {

    // === Usuarios ===
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::get('/usuarios/{usuario}', [UsuarioController::class, 'show']);

    // === Horario (usuarios normales logueados) ===
    Route::get('/horario/{codalu}/{semsem}', [HorarioController::class, 'getHorario']);

    // === Entregas de Tareas ===
    Route::apiResource('entregas', EntregaTareaController::class);

    // === Docentes (CRUD normal de docentes para usuarios internos) ===
    Route::apiResource('docentes', DocenteController::class);

    // === Cursos ===
    Route::apiResource('cursos', CursoController::class);
});

// =========================
//  Rutas exclusivas para PROYECTOS TÉCNICOS
//  (solo accesibles con tokens con ability: docentes:read)
// =========================
Route::middleware(['auth:sanctum', 'abilities:docentes:read'])->group(function () {
    Route::get('/docentes/dni/{dni}', [DocenteController::class, 'getByDni']);
});
