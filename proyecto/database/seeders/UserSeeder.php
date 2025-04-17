<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );

        // Crear usuario profesor
        User::firstOrCreate(
            ['email' => 'profesor@example.com'],
            [
                'name' => 'Profesor',
                'password' => Hash::make('profesor123'),
                'email_verified_at' => now(),
            ]
        );

        // Crear más usuarios de ejemplo
        $usuarios = [
            [
                'name' => 'Carlos Martínez',
                'email' => 'carlos.martinez@example.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'María López',
                'email' => 'maria.lopez@example.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Juan García',
                'email' => 'juan.garcia@example.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Laura Fernández',
                'email' => 'laura.fernandez@example.com',
                'password' => Hash::make('password'),
            ],
        ];

        foreach ($usuarios as $usuario) {
            User::firstOrCreate(
                ['email' => $usuario['email']],
                [
                    'name' => $usuario['name'],
                    'password' => $usuario['password'],
                    'email_verified_at' => now(),
                ]
            );
        }
    }
} 