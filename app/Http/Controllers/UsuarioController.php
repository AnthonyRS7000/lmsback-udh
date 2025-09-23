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
            return redirect()->away(config('app.frontend_url', 'http://localhost:5173/login') . "?error=google");
        }

        // normalizar email
        $email = strtolower(trim((string) $googleUser->getEmail()));

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@udh.edu.pe')) {
            return redirect()->away(config('app.frontend_url', 'http://localhost:5173/login') . "?error=email");
        }

        $codigo = strstr($email, '@', true);

        // consultar API UDH
        try {
            $url = config('udh.apis.estudiante') . $codigo;
            $response = Http::timeout(10)->retry(3, 1000)->get($url);

            if ($response->failed()) {
                return redirect()->away(config('app.frontend_url', 'http://localhost:5173/login') . "?error=api");
            }

            $data = $response->json()[0] ?? null;
            if (!$data) {
                return redirect()->away(config('app.frontend_url', 'http://localhost:5173/login') . "?error=no-data");
            }
        } catch (\Exception $e) {
            return redirect()->away(config('app.frontend_url', 'http://localhost:5173/login') . "?error=api-exception");
        }

        $rolEstudiante = Rol::where('slug', 'estudiante')->first();
        if (!$rolEstudiante) {
            return redirect()->away(config('app.frontend_url', 'http://localhost:5173/login') . "?error=no-role");
        }

        $usuario = Usuario::where('email', $email)->first();

        if (!$usuario) {
            $nombres          = $this->sanitizeString($data['stu_nombres'] ?? '');
            $apellidoPaterno  = $this->sanitizeString($data['stu_apellido_paterno'] ?? '');
            $apellidoMaterno  = $this->sanitizeString($data['stu_apellido_materno'] ?? '');
            $apellidos        = trim($apellidoPaterno . ' ' . $apellidoMaterno);
            $dni              = isset($data['stu_dni']) ? preg_replace('/\D/', '', (string) $data['stu_dni']) : null;

            $usuario = Usuario::create([
                'nombres'          => $nombres,
                'apellidos'        => $apellidos,
                'email'            => $email,
                'role_id'          => $rolEstudiante->id,
                'tipo_documento'   => $dni ? 'DNI' : null,
                'numero_documento' => $dni,
                'password'         => $dni ? $dni : str()->random(16),
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

        // determinar ruta según rol
        $rol = strtolower($usuario->role->slug ?? '');
        $frontend = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');

        switch ($rol) {
            case 'estudiante':
                $redirectPath = "/estudiante";
                break;
            case 'docente':
                $redirectPath = "/docente";
                break;
            case 'administrativo':
                $redirectPath = "/administrativo";
                break;
            case 'escuela':
                $redirectPath = "/escuela";
                break;
            case 'facultad':
                $redirectPath = "/facultad";
                break;
            default:
                $redirectPath = "/";
        }

        // redirigir al frontend con token en query
        return redirect()->away("{$frontend}{$redirectPath}?token={$token}");
    }

    /**
     * Login tradicional (email + contraseña)
     */
    public function login(Request $request)
    {
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

    private function sanitizeString($string)
    {
        return htmlspecialchars(trim((string) $string), ENT_QUOTES, 'UTF-8');
    }
}
