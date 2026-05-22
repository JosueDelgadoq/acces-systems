<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $usersByRole = [
            'admin' => ['name' => 'Administrador', 'email' => 'admin@example.com'],
            'gerente' => ['name' => 'Gerente', 'email' => 'gerente@example.com'],
            'comercial' => ['name' => 'Comercial', 'email' => 'comercial@example.com'],
            'tecnico' => ['name' => 'Tecnico', 'email' => 'tecnico@example.com'],
            'administrativo' => ['name' => 'Administrativo', 'email' => 'administrativo@example.com'],
        ];

        foreach ($usersByRole as $role => $attributes) {
            $user = User::firstOrCreate(
                ['email' => $attributes['email']],
                [
                    'name' => $attributes['name'],
                    'password' => bcrypt('password'),
                ],
            );

            $user->syncRoles([$role]);
        }
    }
}
