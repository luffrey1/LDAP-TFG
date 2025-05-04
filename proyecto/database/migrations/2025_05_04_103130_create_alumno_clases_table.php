<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alumno_clases', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->comment('Nombre del alumno');
            $table->string('apellidos')->comment('Apellidos del alumno');
            $table->string('email')->nullable()->comment('Email del alumno');
            $table->string('dni')->nullable()->comment('DNI o documento de identidad');
            $table->string('numero_expediente')->nullable()->comment('Número de expediente académico');
            $table->date('fecha_nacimiento')->nullable()->comment('Fecha de nacimiento');
            $table->foreignId('clase_grupo_id')->comment('Grupo al que pertenece el alumno');
            $table->foreign('clase_grupo_id')->references('id')->on('clase_grupos')->onDelete('cascade');
            $table->string('ldap_dn')->nullable()->comment('Distinguished Name en LDAP si está vinculado');
            $table->string('usuario_ldap')->nullable()->comment('Nombre de usuario en LDAP');
            $table->boolean('cuenta_creada')->default(false)->comment('Si ya tiene cuenta LDAP creada');
            $table->boolean('activo')->default(true)->comment('Si el alumno está activo o archivado');
            $table->json('metadatos')->nullable()->comment('Datos adicionales en formato JSON');
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla para registrar la actividad de los alumnos en los ordenadores (telemetría)
        Schema::create('alumno_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_clase_id')->comment('ID del alumno');
            $table->foreign('alumno_clase_id')->references('id')->on('alumno_clases')->onDelete('cascade');
            $table->string('ip_ordenador')->nullable()->comment('Dirección IP del ordenador');
            $table->string('nombre_ordenador')->nullable()->comment('Nombre del ordenador');
            $table->string('sistema_operativo')->nullable()->comment('Sistema operativo usado');
            $table->string('navegador')->nullable()->comment('Navegador usado');
            $table->string('tipo_accion')->comment('Tipo de acción: login, logout, etc.');
            $table->text('detalles')->nullable()->comment('Detalles adicionales de la acción');
            $table->timestamp('fecha_hora')->useCurrent()->comment('Fecha y hora de la actividad');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumno_actividades');
        Schema::dropIfExists('alumno_clases');
    }
};
