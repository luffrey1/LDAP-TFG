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
        Schema::create('sistema_config', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();
            $table->text('valor')->nullable();
            $table->string('tipo')->default('string');
            $table->string('descripcion')->nullable();
            $table->foreignId('modificado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
        
        // Insertar configuraciones por defecto
        $this->insertarConfiguracionesIniciales();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sistema_config');
    }
    
    /**
     * Insertar configuraciones iniciales del sistema
     */
    private function insertarConfiguracionesIniciales()
    {
        DB::table('sistema_config')->insert([
            [
                'clave' => 'modulo_calendario_activo',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Activa o desactiva el módulo de calendario',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'clave' => 'modulo_mensajeria_activo',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Activa o desactiva el módulo de mensajería',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'clave' => 'modulo_documentos_activo',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Activa o desactiva el módulo de documentos',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'clave' => 'politica_password_longitud',
                'valor' => '8',
                'tipo' => 'integer',
                'descripcion' => 'Longitud mínima de contraseñas',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'clave' => 'politica_password_mayusculas',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Requerir al menos una letra mayúscula en contraseñas',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'clave' => 'politica_password_numeros',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Requerir al menos un número en contraseñas',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'clave' => 'politica_password_especiales',
                'valor' => 'false',
                'tipo' => 'boolean',
                'descripcion' => 'Requerir al menos un carácter especial en contraseñas',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'clave' => 'vpn_password',
                'valor' => '',
                'tipo' => 'string',
                'descripcion' => 'Contraseña de acceso VPN',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'clave' => 'ssh_acceso_global',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Permitir acceso SSH global',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}; 