<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Presupuesto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PresupuestoFactory extends Factory
{
    protected $model = Presupuesto::class;

    public function definition(): array
    {
        $commercial = User::role('comercial')->first() ?? tap(User::factory()->create(), function (User $user): void {
            $user->assignRole('comercial');
        });
        $lead = Lead::factory()->create();

        $p1 = fake()->randomFloat(2, 500, 10000);
        $p2 = fake()->randomFloat(2, 300, 8000);
        $p3 = fake()->randomFloat(2, 200, 5000);
        $p4 = fake()->randomFloat(2, 100, 3000);

        return [
            'lead_id' => $lead->id,
            'producto_1' => fake()->words(2, true),
            'precio_1' => $p1,
            'producto_2' => fake()->words(2, true),
            'precio_2' => $p2,
            'producto_3' => fake()->words(2, true),
            'precio_3' => $p3,
            'producto_4' => fake()->words(2, true),
            'precio_4' => $p4,
            'presupuesto_definitivo' => $p1 + $p2 + $p3 + $p4,
            'fecha_envio' => fake()->dateTimeBetween('-30 days', 'now'),
            'created_by' => $commercial->id,
        ];
    }
}
