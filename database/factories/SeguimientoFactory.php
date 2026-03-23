<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeguimientoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'fecha_contacto' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'comercial_id' => User::factory(),
            'medio_contacto' => $this->faker->randomElement(['whatsapp', 'llamada_anura_ip', 'mail', 'visita', 'videollamada']),
            'resultado' => $this->faker->sentence(),
            'proxima_accion' => $this->faker->sentence(),
            'fecha_proxima_accion' => $this->faker->dateTimeBetween('now', '+30 days'),
            'observaciones' => $this->faker->paragraph(),
        ];
    }
}

