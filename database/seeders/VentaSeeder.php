<?php

namespace Database\Seeders;

use App\Models\Venta;
use App\Models\User;
use Illuminate\Database\Seeder;

class VentaSeeder extends Seeder
{
    public function run(): void
    {
        $commercial = User::where('role', 'commercial')->first();

        Venta::factory(10)->create([
            'created_by' => $commercial?->id ?? 1,
        ]);
    }
}

