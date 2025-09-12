<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\TokenUdhController;


// Registro/Login con Google
Route::post('/auth/google', [UsuarioController::class, 'store']);
Route::get('/udh/token', [TokenUdhController::class, 'token']);

// Rutas protegidas con Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::get('/usuarios/{usuario}', [UsuarioController::class, 'show']);
});
