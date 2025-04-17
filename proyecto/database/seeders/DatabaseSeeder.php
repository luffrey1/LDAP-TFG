<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Documento;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuarios predeterminados
        $users = [
            [
                'name' => 'Administrador',
                'username' => 'admin',
                'email' => 'admin@ejemplo.com',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ],
            [
                'name' => 'Profesor',
                'username' => 'profesor',
                'email' => 'profesor@ejemplo.com',
                'password' => Hash::make('profesor'),
                'role' => 'profesor',
            ],
            [
                'name' => 'Director',
                'username' => 'director',
                'email' => 'director@ejemplo.com',
                'password' => Hash::make('director'),
                'role' => 'admin',
            ],
            [
                'name' => 'Coordinador TIC',
                'username' => 'coordinador',
                'email' => 'coordinador@ejemplo.com',
                'password' => Hash::make('coordinador'),
                'role' => 'editor',
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        // Crear algunos documentos de ejemplo
        $documentos = [
            [
                'nombre' => 'Programación anual 2024-2025',
                'nombre_original' => 'programacion_anual_2024_2025.pdf',
                'descripcion' => 'Documento de programación anual para el curso 2024-2025',
                'carpeta' => 'programaciones',
                'extension' => 'pdf',
                'tipo' => 'application/pdf',
                'tamaño' => 1254000,
                'ruta' => 'documentos/programaciones/programacion_anual_2024_2025.pdf',
                'subido_por' => 1,
                'activo' => true,
            ],
            [
                'nombre' => 'Horarios primer trimestre',
                'nombre_original' => 'horarios_primer_trimestre.xlsx',
                'descripcion' => 'Horarios de todos los grupos para el primer trimestre',
                'carpeta' => 'horarios',
                'extension' => 'xlsx',
                'tipo' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'tamaño' => 620000,
                'ruta' => 'documentos/horarios/horarios_primer_trimestre.xlsx',
                'subido_por' => 2,
                'activo' => true,
            ],
            [
                'nombre' => 'Acta reunión departamento',
                'nombre_original' => 'acta_reunion_departamento.docx',
                'descripcion' => 'Acta de la última reunión de departamento',
                'carpeta' => 'actas',
                'extension' => 'docx',
                'tipo' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'tamaño' => 450000,
                'ruta' => 'documentos/actas/acta_reunion_departamento.docx',
                'subido_por' => 1,
                'activo' => true,
            ],
        ];

        foreach ($documentos as $docData) {
            Documento::create($docData);
        }
    }
}
