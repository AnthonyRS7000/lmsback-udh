<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carga_academicas', function (Blueprint $table) {
            $table->id();
            $table->string('codper')->unique(); // ej: 0000002030
            $table->string('semsem');           // ej: 2025-2
            $table->string('seccion');

            $table->foreignId('curso_id')
                  ->constrained('cursos')
                  ->onDelete('cascade');

            $table->foreignId('docente_id')
                  ->constrained('docentes')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carga_academicas');
    }
};
