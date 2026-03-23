<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostventaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'producto_instalado' => $this->faker->randomElement([
                'Silla salvaescalera recta',
                'Silla salvaescalera curva',
                'Plataforma inclinada'
            ]),
            'fecha_instalacion' => $this->faker->dateTimeBetween('-60 days', '-10 days'),
            'garantia_activa' => $this->faker->boolean(80),
            'servicio_mantenimiento' => $this->faker->boolean(30),
            'proximo_servicio' => $this->faker->dateTimeBetween('now', '+365 days'),
            'nivel_satisfaccion' => $this->faker->randomElement(['excelente', 'bueno', 'regular', 'mala']),
            'resena_obtenida' => $this->faker->paragraph(),
        ];
    }
}

