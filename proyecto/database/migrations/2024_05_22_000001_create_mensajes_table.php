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
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remitente_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('destinatario_id')->constrained('users')->onDelete('cascade');
            $table->string('asunto');
            $table->longText('contenido');
            $table->boolean('leido')->default(false);
            $table->boolean('destacado')->default(false);
            $table->string('etiqueta')->nullable();
            $table->boolean('borrador')->default(false);
            $table->boolean('eliminado_remitente')->default(false);
            $table->boolean('eliminado_destinatario')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
}; 