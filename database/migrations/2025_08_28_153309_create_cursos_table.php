<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // ej: 062102021
            $table->string('nombre');
            $table->unsignedInteger('ciclo')->nullable();
            $table->unsignedInteger('creditos')->nullable();

            // Relación con docente opcional
            $table->foreignId('docente_id')
                  ->nullable()
                  ->constrained('docentes')
                  ->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};
