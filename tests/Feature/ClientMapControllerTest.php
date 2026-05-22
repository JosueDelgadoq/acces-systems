<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Conservation;
use App\Models\Pendiente;
use App\Services\ClientMapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientMapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_service_classifies_clients_and_validates_coordinates(): void
    {
        $conservationClient = Client::query()->create([
            'name' => 'Cliente Conservacion',
            'address' => 'Calle 1',
            'city' => 'Rosario',
            'latitud' => -32.9442426,
            'longitud' => -60.6505388,
            'direccion_normalizada' => 'Calle 1, Rosario, Santa Fe, Argentina',
        ]);

        Conservation::query()->create([
            'client_id' => $conservationClient->id,
            'start_date' => now()->subMonth()->toDateString(),
            'expiration_date' => now()->addMonth()->toDateString(),
            'frequency' => 'mensual',
        ]);

        $operationalClient = Client::query()->create([
            'name' => 'Cliente Operativo',
            'address' => 'Calle 2',
            'city' => 'Rosario',
            'latitud' => -32.95,
            'longitud' => -60.64,
        ]);

        Pendiente::query()->create([
            'client_id' => $operationalClient->id,
            'type' => 'reclamo',
            'description' => 'Pendiente activo',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'alta',
        ]);

        $standardClient = Client::query()->create([
            'name' => 'Cliente General',
            'address' => 'Sin geocodificar',
            'city' => 'Funes',
            'latitud' => 0,
            'longitud' => 0,
        ]);

        $payload = app(ClientMapService::class)->buildPayload()->keyBy('name');

        $this->assertSame('conservation', $payload['Cliente Conservacion']['client_type']);
        $this->assertSame('verified', $payload['Cliente Conservacion']['coordinates_quality']);
        $this->assertTrue($payload['Cliente Conservacion']['has_coordinates']);

        $this->assertSame('operational', $payload['Cliente Operativo']['client_type']);
        $this->assertSame(1, $payload['Cliente Operativo']['active_pendientes_count']);

        $this->assertSame('standard', $payload['Cliente General']['client_type']);
        $this->assertFalse($payload['Cliente General']['has_coordinates']);
        $this->assertSame('missing', $payload['Cliente General']['coordinates_quality']);
    }
}
