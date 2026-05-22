<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

class VentaFactory extends Factory
{
    protected $model = Venta::class;

    public function definition(): array
    {
        $commercial = User::role('comercial')->first() ?? tap(User::factory()->create(), function (User $user): void {
            $user->assignRole('comercial');
        });
        $lead = Lead::factory()->create();

        return [
            'lead_id' => $lead->id,
            'producto_instalado' => $lead->producto_interes ?? fake()->words(3, true),
            'monto_total' => fake()->randomFloat(2, 2000, 50000),
            'estado' => fake()->randomElement(['Cerrada', 'Cancelada']),
            'fecha_cierre' => fake()->dateTimeBetween('-20 days', 'now'),
            'created_by' => $commercial->id,
        ];
    }
}
