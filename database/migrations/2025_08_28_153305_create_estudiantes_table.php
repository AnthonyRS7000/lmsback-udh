<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();

            // Relación 1-1 con usuarios
            $table->foreignId('usuario_id')
                ->constrained('usuarios')
                ->cascadeOnDelete();

            // Si manejas escuelas
            $table->foreignId('escuela_id')
                ->nullable()
                ->constrained('escuelas')
                ->nullOnDelete();

            // Datos académicos
            $table->string('codigo')->unique();           // código universitario
            $table->date('fecha_ingreso')->nullable();
            $table->enum('estado', ['activo','suspendido','egresado','retirado'])->default('activo');

            $table->softDeletes();
            $table->timestamps();

            $table->index(['escuela_id','estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
