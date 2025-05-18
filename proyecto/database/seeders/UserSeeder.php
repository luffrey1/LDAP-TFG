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
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );

        // Crear usuario profesor
        User::firstOrCreate(
            ['email' => 'profesor@example.com'],
            [
                'name' => 'Profesor',
                'username' => 'profesor',
                'password' => Hash::make('profesor123'),
                'email_verified_at' => now(),
            ]
        );

        // Crear más usuarios de ejemplo
        $usuarios = [
            [
                'name' => 'Carlos Martínez',
                'username' => 'carlos.martinez',
                'email' => 'carlos.martinez@example.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'María López',
                'username' => 'maria.lopez',
                'email' => 'maria.lopez@example.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Juan García',
                'username' => 'juan.garcia',
                'email' => 'juan.garcia@example.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Laura Fernández',
                'username' => 'laura.fernandez',
                'email' => 'laura.fernandez@example.com',
                'password' => Hash::make('password'),
            ],
        ];

        foreach ($usuarios as $usuario) {
            User::firstOrCreate(
                ['email' => $usuario['email']],
                [
                    'name' => $usuario['name'],
                    'username' => $usuario['username'],
                    'password' => $usuario['password'],
                    'email_verified_at' => now(),
                ]
            );
        }
    }
} 