<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

// Buscar el usuario admin
$user = User::where('email', 'admin@test.com')->first();

if ($user) {
    // Actualizar el rol a admin
    $user->role = 'admin';
    $user->save();
    
    echo "Usuario actualizado: " . $user->email . " - Rol: " . $user->role . "\n";
} else {
    // Crear el usuario si no existe
    $user = User::create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);
    
    echo "Usuario creado: " . $user->email . "\n";
}

