<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();     // ej: Admin, Docente, Estudiante
            $table->string('slug')->unique();       // ej: admin, docente, estudiante
            $table->string('descripcion')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
