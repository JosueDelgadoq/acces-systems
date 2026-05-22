<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\Presupuesto;
use App\Models\User;
use Illuminate\Database\Seeder;

class PresupuestoSeeder extends Seeder
{
    public function run(): void
    {
        $commercial = User::role('comercial')->first();

        Presupuesto::factory(15)->create([
            'lead_id' => Lead::query()->inRandomOrder()->first()?->id,
            'created_by' => $commercial?->id ?? User::query()->value('id') ?? 1,
        ]);
    }
}
