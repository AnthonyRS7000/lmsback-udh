<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\TokenUdhController;

// === Autenticación con Google ===
Route::get('/auth/google', [UsuarioController::class, 'redirectToGoogle']);   // Paso 1: redirección
Route::get('/auth/google/callback', [UsuarioController::class, 'handleGoogleCallback']); // Paso 2: callback

// === Token del sistema antiguo (ejemplo) ===
Route::get('/udh/token', [TokenUdhController::class, 'token']);

// === Rutas protegidas con Sanctum ===
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::get('/usuarios/{usuario}', [UsuarioController::class, 'show']);
});
