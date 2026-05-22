<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Services\GeolocalizacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeocodeClientsMapCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_geocodes_clients_without_deleting_data(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Geocodificar',
            'address' => 'Calle Falsa 123',
            'city' => 'Rosario',
            'state' => 'Santa Fe',
            'postal_code' => '2000',
        ]);

        $service = $this->mock(GeolocalizacionService::class);
        $service->shouldReceive('geocodeClientAddress')
            ->once()
            ->with('Calle Falsa 123', 'Rosario', 'Santa Fe', '2000')
            ->andReturn([
                'latitud' => -32.9442426,
                'longitud' => -60.6505388,
                'direccion_formateada' => 'Calle Falsa 123, Rosario, Santa Fe, Argentina',
            ]);

        $this->artisan('clients:geocode-map', ['--client_id' => [$client->id]])
            ->assertExitCode(0);

        $client->refresh();

        $this->assertSame('Cliente Geocodificar', $client->name);
        $this->assertSame('-32.9442426', (string) $client->latitud);
        $this->assertSame('-60.6505388', (string) $client->longitud);
        $this->assertSame('Calle Falsa 123, Rosario, Santa Fe, Argentina', $client->direccion_normalizada);
        $this->assertDatabaseCount('clients', 1);
    }

    public function test_command_skips_clients_without_address_even_if_other_location_fields_exist(): void
    {
        Client::query()->create([
            'name' => 'Cliente Sin Direccion',
            'city' => 'Rosario',
            'state' => 'Santa Fe',
            'postal_code' => '2000',
        ]);

        $service = $this->mock(GeolocalizacionService::class);
        $service->shouldNotReceive('geocodeClientAddress');

        $this->artisan('clients:geocode-map')
            ->expectsOutput('No hay clientes pendientes para geocodificar.')
            ->assertExitCode(0);
    }
}
