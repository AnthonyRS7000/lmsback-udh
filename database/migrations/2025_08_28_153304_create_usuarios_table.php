<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();

            // Identidad
            $table->string('nombres');
            $table->string('apellidos');

            // Documento (Perú: DNI/CE/PASAPORTE)
            $table->enum('tipo_documento', ['DNI','CE','PASAPORTE'])->default('DNI');
            $table->string('numero_documento', 12)->nullable();
            $table->unique(['tipo_documento','numero_documento'], 'usuarios_doc_unique');

            // Contacto / rol
            $table->string('email')->unique();
            $table->string('telefono')->nullable();
            $table->enum('rol', ['admin','estudiante','docente'])->default('estudiante');

            // Autenticación local
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            // 🔐 Soporte OAuth (Google u otros)
            $table->string('google_id')->nullable()->unique();   // único, permite múltiples NULL
            $table->string('google_avatar')->nullable();
            $table->string('provider')->nullable();               // 'google'
            $table->string('provider_id')->nullable();            // id del proveedor
            $table->unique(['provider','provider_id'], 'usuarios_provider_unique');

            // Control
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
