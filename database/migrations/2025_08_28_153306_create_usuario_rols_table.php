<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_rol', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')
                ->constrained('usuarios')
                ->cascadeOnDelete();

            $table->foreignId('rol_id')
                ->constrained('roles')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['usuario_id','rol_id'], 'usuario_rol_unique');
            $table->index(['rol_id','usuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_rol');
    }
};
