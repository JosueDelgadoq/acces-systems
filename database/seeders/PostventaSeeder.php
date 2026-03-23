<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\Postventa;
use Illuminate\Database\Seeder;

class PostventaSeeder extends Seeder
{
    public function run(): void
    {
        // Solo para leads con resultado 'Vendido'
        Lead::where('resultado_final', 'Vendido')->get()->each(function ($lead) {
            Postventa::factory()->create(['lead_id' => $lead->id]);
        });
    }
}

