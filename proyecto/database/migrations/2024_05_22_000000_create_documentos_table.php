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
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('nombre_original');
            $table->text('descripcion')->nullable();
            $table->string('carpeta')->default('general');
            $table->string('extension', 10);
            $table->string('tipo');
            $table->bigInteger('tamaño');
            $table->string('ruta');
            $table->foreignId('subido_por')->constrained('users')->onDelete('cascade');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
}; 