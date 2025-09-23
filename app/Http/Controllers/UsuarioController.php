<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Estudiante;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class UsuarioController extends Controller
{
    /**
     * Paso 1: Redirigir al login de Google
     */
    public function redirectToGoogle(Request $request)
    {
        $state = $request->get('state');
        $socialite = Socialite::driver('google')->stateless();

        if ($state) {
            $validator = Validator::make(['state' => $state], [
                'state' => 'string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 'State inválido'], 400);
            }

            $socialite = $socialite->with(['state' => $state]);
        }

        return $socialite->redirect();
    }

    /**
     * Paso 2: Callback desde Google
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return $this->errorResponse('Error en la autenticación con Google', 500);
        }

        // normalizar email
        $email = strtolower(trim((string) $googleUser->getEmail()));

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->errorResponse('Email inválido', 400);
        }

        if (!str_ends_with($email, '@udh.edu.pe')) {
            return $this->errorResponse('Solo se permiten correos institucionales UDH', 403);
        }

        $codigo = strstr($email, '@', true);
        if (empty($codigo) || !preg_match('/^[a-zA-Z0-9._-]+$/', $codigo)) {
            return $this->errorResponse('Código de usuario inválido', 400);
        }

        // consultar API UDH
        try {
            $url = config('udh.apis.estudiante') . $codigo;
            $response = Http::timeout(10)->retry(3, 1000)->get($url);

            if ($response->failed()) {
                return $this->errorResponse('No se pudo conectar con la API UDH', 500);
            }

            $data = $response->json()[0] ?? null;
            if (!$data) {
                return $this->errorResponse('Código no encontrado en UDH', 404);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Error al consultar datos institucionales', 500);
        }

        $rolEstudiante = Rol::where('slug', 'estudiante')->first();
        if (!$rolEstudiante) {
            return $this->errorResponse('Rol de estudiante no configurado', 500);
        }

        $usuario = Usuario::where('email', $email)->first();

        if (!$usuario) {
            $nombres          = $this->sanitizeString($data['stu_nombres'] ?? '');
            $apellidoPaterno  = $this->sanitizeString($data['stu_apellido_paterno'] ?? '');
            $apellidoMaterno  = $this->sanitizeString($data['stu_apellido_materno'] ?? '');
            $apellidos        = trim($apellidoPaterno . ' ' . $apellidoMaterno);

            if (empty($nombres) || empty($apellidos)) {
                return $this->errorResponse('Datos incompletos del estudiante', 400);
            }

            // ✅ limpiar DNI a solo números
            $dni = isset($data['stu_dni']) ? preg_replace('/\D/', '', (string) $data['stu_dni']) : null;

            // ⚠️ Importante: NO usar bcrypt aquí; tu mutator lo hashea.
            $usuario = Usuario::create([
                'nombres'          => $nombres,
                'apellidos'        => $apellidos,
                'email'            => $email,
                'role_id'          => $rolEstudiante->id,
                'tipo_documento'   => $dni ? 'DNI' : null,
                'numero_documento' => $dni,
                'password'         => $dni ? $dni : str()->random(16), // ← el mutator hará el hash
                'google_id'        => $googleUser->getId(),
                'google_avatar'    => $googleUser->getAvatar(),
                'provider'         => 'google',
                'provider_id'      => $googleUser->getId(),
            ]);

            Estudiante::create([
                'usuario_id'    => $usuario->id,
                'escuela_id'    => null,
                'codigo'        => $this->sanitizeString($data['stu_codigo'] ?? $codigo),
                'fecha_ingreso' => now(),
                'estado'        => 'activo',
            ]);
        }

        // login y token sanctum
        Auth::login($usuario);
        $token = $usuario->createToken('auth_token', ['*'], now()->addHours(24))->plainTextToken;

        $state = $request->get('state');

        $userData = [
            'id'        => $usuario->id,
            'nombres'   => $usuario->nombres,
            'apellidos' => $usuario->apellidos,
            'email'     => $usuario->email,
            'rol'       => $usuario->role->slug ?? null,
        ];

        $udhData = [
            'nombres'           => $this->sanitizeString($data['stu_nombres'] ?? ''),
            'apellido_paterno'  => $this->sanitizeString($data['stu_apellido_paterno'] ?? ''),
            'apellido_materno'  => $this->sanitizeString($data['stu_apellido_materno'] ?? ''),
            'dni'               => isset($data['stu_dni']) ? preg_replace('/\D/', '', (string) $data['stu_dni']) : null,
            'codigo'            => $this->sanitizeString($data['stu_codigo'] ?? ''),
            'facultad'          => $this->sanitizeString($data['stu_facultad'] ?? ''),
            'programa'          => $this->sanitizeString($data['stu_programa'] ?? ''),
            'ciclo'             => $this->sanitizeString($data['stu_ciclo'] ?? ''),
        ];

        $allowedOrigins = [
            config('app.frontend_url', 'http://localhost:5173'),
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'https://tu-frontend.com'
        ];
        $targetOrigin = $allowedOrigins[0];

        return response()->make("
          <script>
            if (window.opener) {
              const targetOrigin = '{$targetOrigin}';
              window.opener.postMessage({
                type: 'google-auth-success',
                usuario: " . json_encode($userData) . ",
                datos_udh: " . json_encode($udhData) . ",
                foto: '" . htmlspecialchars($googleUser->getAvatar()) . "',
                token: '" . htmlspecialchars($token) . "'" . 
                ($state ? ",\nstate: '" . htmlspecialchars($state) . "'" : '') . "
              }, '*');
            }
            window.close();
          </script>
        ", 200, [
            'Content-Type' => 'text/html',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff'
        ]);
    }

    /**
     * Login tradicional (email + contraseña)
     */
    public function login(Request $request)
    {
        // normalizar email
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');

        if (!$email || !$password) {
            return response()->json(['error' => 'Email y contraseña son obligatorios'], 422);
        }

        if (!Auth::attempt(['email' => $email, 'password' => $password])) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        /** @var \App\Models\Usuario $usuario */
        $usuario = Auth::user();
        $token = $usuario->createToken('auth_token', ['*'], now()->addHours(24))->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'token'   => $token,
            'usuario' => [
                'id'        => $usuario->id,
                'nombres'   => $usuario->nombres,
                'apellidos' => $usuario->apellidos,
                'email'     => $usuario->email,
                'rol'       => $usuario->role->slug ?? null,
            ]
        ]);
    }

    /**
     * Sanitizar strings
     */
    private function sanitizeString($string)
    {
        return htmlspecialchars(trim((string) $string), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Respuesta de error consistente
     */
    private function errorResponse($message, $status = 400)
    {
        return response()->make("
          <script>
            if (window.opener) {
              window.opener.postMessage({
                type: 'google-auth-error',
                message: '" . htmlspecialchars($message) . "'
              }, '*');
            }
            window.close();
          </script>
        ", $status, [
            'Content-Type' => 'text/html',
            'X-Frame-Options' => 'SAMEORIGIN'
        ]);
    }
}
