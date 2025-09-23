<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matricula_detalles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('matricula_id')
                  ->constrained('matriculas')
                  ->onDelete('cascade');

            $table->foreignId('carga_id')
                  ->constrained('carga_academicas')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matricula_detalles');
    }
};
