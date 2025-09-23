<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Usuario;
use App\Models\Docente;

class ImportarDocentes extends Command
{
    protected $signature = 'docentes:importar';
    protected $description = 'Importa docentes desde API externo y los guarda en usuarios y docentes';

    public function handle()
    {
        $url = "http://www.udh.edu.pe/websauh/apis/DocentesAPI.aspx?action=all&token=a402925d85ca5774ddcdf794faee34ed08a2bab78d22ec1de5baa0145f1528e4";
        $response = Http::get($url);

        if (!$response->successful()) {
            $this->error("❌ Error al conectar con la API: " . $response->status());
            return;
        }

        $data = $response->json();

        if (!isset($data['data'])) {
            $this->error("❌ La respuesta no contiene datos de docentes");
            return;
        }

        $importados = 0;

        foreach ($data['data'] as $docenteApi) {
            $codigo   = trim($docenteApi['id']);          // código único API
            $dni      = trim($docenteApi['documento']);   // documento (DNI)
            $email    = isset($docenteApi['email']) ? trim($docenteApi['email']) : null;
            $nombres  = trim($docenteApi['name']);
            $apellidos= trim($docenteApi['apellidos']);
            $grado    = trim($docenteApi['grado']);
            $telefono = trim($docenteApi['telefono']);

            /**
             * 1. Crear/actualizar en USUARIOS
             * Clave: numero_documento
             */
            $usuario = Usuario::firstOrCreate(
                ['numero_documento' => $dni],
                [
                    'nombres'        => $nombres,
                    'apellidos'      => $apellidos,
                    'tipo_documento' => 'DNI',
                    'email'          => $email,
                    'telefono'       => $telefono,
                    'role_id'        => 2, // rol docente
                    'password'       => bcrypt($dni), // contraseña inicial = documento
                ]
            );

            // actualizar si ya existía (menos la contraseña)
            $usuario->update([
                'nombres'        => $nombres,
                'apellidos'      => $apellidos,
                'tipo_documento' => 'DNI',
                'email'          => $email,
                'telefono'       => $telefono,
                'role_id'        => 2,
            ]);

            /**
             * 2. Crear/actualizar en DOCENTES
             * Clave: codigo_do (id de API)
             */
            Docente::updateOrCreate(
                ['codigo_do' => $codigo],
                [
                    'email'    => $email,
                    'grado'    => $grado,
                    'telefono' => $telefono,
                ]
            );

            $importados++;
        }

        $this->info("✅ Se importaron/actualizaron $importados docentes correctamente.");
    }
}
