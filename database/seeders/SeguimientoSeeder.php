<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\Seguimiento;
use App\Models\User;
use Illuminate\Database\Seeder;

class SeguimientoSeeder extends Seeder
{
    public function run(): void
    {
        $comerciales = User::role('comercial')->take(3)->pluck('id');

        Lead::query()->get()->each(function ($lead) use ($comerciales) {
            if ($comerciales->isEmpty()) {
                return;
            }

            Seguimiento::factory()
                ->count(rand(1, 5))
                ->create([
                    'lead_id' => $lead->id,
                    'comercial_id' => $comerciales->random(),
                ]);
        });
    }
}
