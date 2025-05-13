<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SistemaConfig;

class InstallModuloMonitoreoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si ya existe la configuración
        $exists = SistemaConfig::where('clave', 'modulo_monitoreo_activo')->exists();
        
        if (!$exists) {
            // Crear la configuración del módulo de monitoreo si no existe
            SistemaConfig::create([
                'clave' => 'modulo_monitoreo_activo',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Activa o desactiva el módulo de monitoreo de equipos',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $this->command->info('✅ Configuración del módulo de monitoreo instalada correctamente');
        } else {
            $this->command->warn('⚠️ La configuración del módulo de monitoreo ya existía');
        }
    }
} 