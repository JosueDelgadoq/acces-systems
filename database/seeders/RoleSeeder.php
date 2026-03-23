<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['admin', 'commercial', 'supervisor', 'tecnico'];
        foreach ($roles as $role) {
            User::firstOrCreate(
                ['email' => $role . '@example.com'],
                [
                    'name' => ucfirst($role),
                    'password' => bcrypt('password'),
                    'role' => $role,
                ]
            );
        }
    }
}

