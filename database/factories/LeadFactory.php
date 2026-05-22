<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        $commercial = User::role('comercial')->first() ?? tap(User::factory()->create(), function (User $user): void {
            $user->assignRole('comercial');
        });

        return [
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'telefono' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'localidad' => fake()->city(),
            'provincia' => fake()->state(),
            'zona_comercial' => fake()->word(),
            'tipo_cliente' => fake()->randomElement(['Residencial', 'Público', 'Empresa', 'Constructor']),
            'subtipo_publico' => fake()->randomElement(['Universidad', 'Municipalidad', 'Banco', 'Hospital', 'Otro']),
            'producto_interes' => fake()->words(2, true),
            'tipo_instalacion' => fake()->randomElement(['Recta', 'Curva', 'Exterior', 'Piscina', 'Vertical']),
            'documentacion_cliente' => fake()->paragraph(),
            'cliente_envio_fotos' => fake()->boolean(30),
            'cliente_envio_planos' => fake()->boolean(20),
            'requiere_relevamiento_pago' => fake()->boolean(40),
            'orientacion_dada' => fake()->boolean(50),
            'tipo_orientacion' => fake()->randomElement([
                'Verbal telefónica',
                'Audio WhatsApp',
                'Texto WhatsApp',
                'Estimado estructurado WhatsApp',
                'PDF enviado por WhatsApp',
                'PDF enviado por Mail',
            ]),
            'fecha_orientacion' => fake()->dateTimeBetween('-15 days', 'now'),
            'estado_pipeline' => fake()->randomElement([
                'Ingresado', 'Contactado', 'Orientacion dada', 'Cotizacion enviada',
                'Presupuesto definitivo enviado', 'Venta cerrada', 'Perdido', 'Postergado',
            ]),
            'resultado_final' => fake()->randomElement(['Abierto', 'Vendido', 'Perdido']),
            'motivo_perdida' => fake()->randomElement([
                'Precio', 'Forma de pago', 'Tiempo entrega', 'Competencia',
                'Calidad percibida', 'Falta decisión', 'Otro',
            ]),
            'canal_origen' => fake()->randomElement(['whatsapp', 'redes_sociales', 'mail', 'telefono']),
            'comercial_asignado_id' => $commercial->id,
            'created_by' => $commercial->id,
        ];
    }
}
