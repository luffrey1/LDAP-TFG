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
        Schema::create('clase_grupos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->comment('Nombre del grupo o clase');
            $table->text('descripcion')->nullable()->comment('Descripción opcional del grupo');
            $table->string('nivel')->nullable()->comment('Nivel académico: ESO, Bachillerato, etc.');
            $table->string('curso')->nullable()->comment('Curso: 1º, 2º, etc.');
            $table->string('seccion')->nullable()->comment('Sección o letra del grupo: A, B, C, etc.');
            $table->string('codigo')->nullable()->comment('Código único del grupo');
            $table->foreignId('profesor_id')->comment('ID del profesor tutor principal');
            $table->foreign('profesor_id')->references('id')->on('users')->onDelete('cascade');
            $table->boolean('activo')->default(true)->comment('Si el grupo está activo o archivado');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clase_grupos');
    }
};
