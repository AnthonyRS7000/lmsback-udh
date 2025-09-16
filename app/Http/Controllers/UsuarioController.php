<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Estudiante;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class UsuarioController extends Controller
{
    /**
     * Paso 1: Redirigir al login de Google
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Paso 2: Callback desde Google
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error en la autenticación con Google'], 500);
        }

        $email = $googleUser->getEmail();

        // Validar dominio institucional
        if (!str_ends_with($email, '@udh.edu.pe')) {
            return response()->json(['error' => 'Solo se permiten correos institucionales UDH'], 403);
        }

        // Extraer el código (antes del @)
        $codigo = strstr($email, '@', true);

        // Consultar API UDH
        $url = config('udh.apis.estudiante') . $codigo;
        $response = Http::timeout(10)->get($url);

        if ($response->failed()) {
            return response()->json(['error' => 'No se pudo conectar con la API UDH'], 500);
        }

        $data = $response->json()[0] ?? null;

        if (!$data) {
            return response()->json(['error' => 'Código no encontrado en UDH'], 404);
        }

        // Verificar si ya existe en BD
        $usuario = Usuario::where('email', $email)->first();

        if (!$usuario) {
            // Crear usuario
            $usuario = Usuario::create([
                'nombres'   => $data['stu_nombres'] ?? '',
                'apellidos' => trim(($data['stu_apellido_paterno'] ?? '') . ' ' . ($data['stu_apellido_materno'] ?? '')),
                'email'     => $email,
                'rol'       => 'estudiante',
                'password'  => bcrypt(str()->random(16)),
            ]);

            // Crear estudiante
            Estudiante::create([
                'usuario_id'    => $usuario->id,
                'escuela_id'    => null,
                'codigo'        => $data['stu_codigo'] ?? $codigo,
                'fecha_ingreso' => now(),
                'estado'        => 'activo',
            ]);
        }

        // Autenticamos y generamos token con Sanctum
        Auth::login($usuario);
        $token = $usuario->createToken('auth_token')->plainTextToken;

        // 🔹 Respuesta final con datos de UDH + foto Google + token
        return response()->json([
            'message' => 'Login exitoso con Google',
            'datos_udh' => [
                'nombres'   => $data['stu_nombres'] ?? '',
                'apellido_paterno' => $data['stu_apellido_paterno'] ?? '',
                'apellido_materno' => $data['stu_apellido_materno'] ?? '',
                'dni'       => $data['stu_dni'] ?? '',
                'codigo'    => $data['stu_codigo'] ?? '',
                'facultad'  => $data['stu_facultad'] ?? '',
                'programa'  => $data['stu_programa'] ?? '',
                'ciclo'     => $data['stu_ciclo'] ?? '',
            ],
            'foto'  => $googleUser->getAvatar(),
            'token' => $token
        ]);
    }
}
