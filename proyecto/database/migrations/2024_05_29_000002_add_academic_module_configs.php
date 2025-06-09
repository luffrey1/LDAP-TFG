<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insertar configuraciones para los módulos académicos
        DB::table('sistema_config')->insert([
            [
                'clave' => 'modulo_mis_clases_activo',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Activa o desactiva el módulo de mis clases',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'clave' => 'modulo_gestion_alumnos_activo',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Activa o desactiva el módulo de gestión de alumnos',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sistema_config')
            ->whereIn('clave', ['modulo_mis_clases_activo', 'modulo_gestion_alumnos_activo'])
            ->delete();
    }
}; 