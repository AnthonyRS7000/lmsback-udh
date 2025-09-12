<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class UsuarioController extends Controller
{
    public function store(Request $request)
    {
        $email = $request->input('email');

        if (!str_ends_with($email, '@udh.edu.pe')) {
            return response()->json(['error' => 'Solo se permiten correos institucionales UDH'], 403);
        }

        $codigo = strstr($email, '@', true);

        $usuario = Usuario::where('email', $email)->first();

        if (!$usuario) {
            $url = "http://www.udh.edu.pe/websauh/secretaria_general/gradosytitulos/datos_estudiante_json.aspx?_c_3456={$codigo}";
            $response = Http::get($url);

            if ($response->failed()) {
                return response()->json(['error' => 'No se pudo conectar con la API UDH'], 500);
            }

            $data = $response->json()[0] ?? null;

            if (!$data) {
                return response()->json(['error' => 'Código no encontrado en UDH'], 404);
            }

            $usuario = Usuario::create([
                'nombres'   => $data['stu_nombres'],
                'apellidos' => $data['stu_apellido_paterno'] . ' ' . $data['stu_apellido_materno'],
                'email'     => $email,
                'rol'       => 'estudiante',
                'password'  => bcrypt(str()->random(16)),
            ]);

            Estudiante::create([
                'usuario_id'    => $usuario->id,
                'escuela_id'    => null,
                'codigo'        => $data['stu_codigo'],
                'fecha_ingreso' => now(),
                'estado'        => 'activo',
            ]);
        }

        Auth::login($usuario);
        $token = $usuario->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'usuario' => $usuario->load('estudiante'),
            'token'   => $token
        ]);
    }
}
