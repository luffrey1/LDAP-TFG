<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('sistema_config')->updateOrInsert(
            ['clave' => 'modulo_clases_activo'],
            [
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Activa o desactiva el módulo de gestión de clases',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Si quieres eliminar el registro al hacer rollback, descomenta la siguiente línea:
        // DB::table('sistema_config')->where('clave', 'modulo_clases_activo')->delete();
    }
}; 