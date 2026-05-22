<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        $commercial = User::role('comercial')->first();

        Lead::factory(10)->create([
            'created_by' => $commercial?->id ?? User::query()->value('id') ?? 1,
        ]);
    }
}
