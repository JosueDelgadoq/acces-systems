<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Seeder;

class VentaSeeder extends Seeder
{
    public function run(): void
    {
        $commercial = User::role('comercial')->first();

        Venta::factory(10)->create([
            'created_by' => $commercial?->id ?? User::query()->value('id') ?? 1,
        ]);
    }
}
