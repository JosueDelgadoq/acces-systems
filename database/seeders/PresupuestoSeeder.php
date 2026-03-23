<?php

namespace Database\Seeders;

use App\Models\Presupuesto;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;

class PresupuestoSeeder extends Seeder
{
    public function run(): void
    {
        $commercial = User::where('role', 'commercial')->first();

        Presupuesto::factory(15)->create([
            'lead_id' => Lead::inRandomOrder()->first()?->id,
            'created_by' => $commercial?->id ?? 1,
        ]);
    }
}

