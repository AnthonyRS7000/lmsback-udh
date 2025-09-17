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
     * Paso 1: Redirigir al login de Google con state para CSRF protection
     */
    public function redirectToGoogle(Request $request)
    {
        // Validar y sanitizar el state si se proporciona
        $state = $request->get('state');
        
        $socialite = Socialite::driver('google')->stateless();
        
        if ($state) {
            // Validar que el state sea una cadena base64 válida
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

        $email = $googleUser->getEmail();

        // Validar que el email sea válido y del dominio institucional
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->errorResponse('Email inválido', 400);
        }

        if (!str_ends_with($email, '@udh.edu.pe')) {
            return $this->errorResponse('Solo se permiten correos institucionales UDH', 403);
        }

        // Extraer y validar el código
        $codigo = strstr($email, '@', true);
        if (empty($codigo) || !preg_match('/^[a-zA-Z0-9._-]+$/', $codigo)) {
            return $this->errorResponse('Código de usuario inválido', 400);
        }

        // Consultar API UDH con timeout y manejo de errores
        try {
            $url = config('udh.apis.estudiante') . $codigo;
            $response = Http::timeout(10)
                ->retry(3, 1000)
                ->get($url);

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

        // Buscar rol estudiante
        $rolEstudiante = Rol::where('slug', 'estudiante')->first();
        if (!$rolEstudiante) {
            return $this->errorResponse('Rol de estudiante no configurado', 500);
        }

        // Verificar si ya existe en BD
        $usuario = Usuario::where('email', $email)->first();

        if (!$usuario) {
            // Sanitizar y validar datos antes de crear
            $nombres = $this->sanitizeString($data['stu_nombres'] ?? '');
            $apellidoPaterno = $this->sanitizeString($data['stu_apellido_paterno'] ?? '');
            $apellidoMaterno = $this->sanitizeString($data['stu_apellido_materno'] ?? '');
            $apellidos = trim($apellidoPaterno . ' ' . $apellidoMaterno);

            if (empty($nombres) || empty($apellidos)) {
                return $this->errorResponse('Datos incompletos del estudiante', 400);
            }

            // Crear usuario
            $usuario = Usuario::create([
                'nombres'   => $nombres,
                'apellidos' => $apellidos,
                'email'     => $email,
                'role_id'   => $rolEstudiante->id,
                'password'  => bcrypt(str()->random(32)), // Contraseña más segura
            ]);

            // Crear estudiante
            Estudiante::create([
                'usuario_id'    => $usuario->id,
                'escuela_id'    => null,
                'codigo'        => $this->sanitizeString($data['stu_codigo'] ?? $codigo),
                'fecha_ingreso' => now(),
                'estado'        => 'activo',
            ]);
        }

        // Autenticamos y generamos token con Sanctum
        Auth::login($usuario);
        $token = $usuario->createToken('auth_token', ['*'], now()->addHours(24))->plainTextToken;

        // Obtener el state del request para devolverlo
        $state = $request->get('state');

        // Preparar datos seguros para enviar al frontend
        $userData = [
            'id' => $usuario->id,
            'nombres' => $usuario->nombres,
            'apellidos' => $usuario->apellidos,
            'email' => $usuario->email,
            'rol' => $usuario->role->slug ?? null,
        ];

        $udhData = [
            'nombres' => $this->sanitizeString($data['stu_nombres'] ?? ''),
            'apellido_paterno' => $this->sanitizeString($data['stu_apellido_paterno'] ?? ''),
            'apellido_materno' => $this->sanitizeString($data['stu_apellido_materno'] ?? ''),
            'dni' => $this->sanitizeString($data['stu_dni'] ?? ''),
            'codigo' => $this->sanitizeString($data['stu_codigo'] ?? ''),
            'facultad' => $this->sanitizeString($data['stu_facultad'] ?? ''),
            'programa' => $this->sanitizeString($data['stu_programa'] ?? ''),
            'ciclo' => $this->sanitizeString($data['stu_ciclo'] ?? ''),
        ];

        // Obtener orígenes permitidos desde configuración
        $allowedOrigins = [
            config('app.frontend_url', 'http://localhost:5173'),
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'https://tu-frontend.com' // Reemplaza por tu dominio real
        ];
        
        // Usar el primer origen válido para enviar el mensaje
        $targetOrigin = $allowedOrigins[0];

        return response()->make("
          <script>
            // Validar que window.opener existe
            if (window.opener) {
              const targetOrigin = '{$targetOrigin}';
              
              window.opener.postMessage({
                type: 'google-auth-success',
                usuario: " . json_encode($userData) . ",
                datos_udh: " . json_encode($udhData) . ",
                foto: '" . htmlspecialchars($googleUser->getAvatar()) . "',
                token: '" . htmlspecialchars($token) . "'" . 
                ($state ? ",\nstate: '" . htmlspecialchars($state) . "'" : '') . "
              }, '*'); // Temporalmente usar * para debug
            }
            window.close();
          </script>
        ", 200, [
            'Content-Type' => 'text/html',
            'X-Frame-Options' => 'SAMEORIGIN', // Cambiado de DENY a SAMEORIGIN
            'X-Content-Type-Options' => 'nosniff'
        ]);
    }

    /**
     * Sanitizar strings para prevenir XSS
     */
    private function sanitizeString($string)
    {
        return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
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
              }, '*'); // Usar * para errores para asegurar entrega
            }
            window.close();
          </script>
        ", $status, [
            'Content-Type' => 'text/html',
            'X-Frame-Options' => 'SAMEORIGIN'
        ]);
    }
}