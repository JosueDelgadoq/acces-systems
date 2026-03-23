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
        $comerciales = User::where('role', 'comercial')->take(3)->pluck('id');

        Lead::all()->each(function ($lead) use ($comerciales) {
            Seguimiento::factory()
                ->count(rand(1, 5))
                ->create([
                    'lead_id' => $lead->id,
                    'comercial_id' => $comerciales->random(),
                ]);
        });
    }
}

