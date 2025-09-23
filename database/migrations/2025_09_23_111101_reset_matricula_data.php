<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Desactivar restricciones FK temporalmente
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('matricula_detalles')->truncate();
        DB::table('horarios')->truncate();
        DB::table('carga_academicas')->truncate();
        DB::table('matriculas')->truncate();
        DB::table('cursos')->truncate();

        // Activar restricciones FK
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        // No es necesario revertir porque solo limpia datos
    }
};
