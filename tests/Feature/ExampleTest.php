<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Pendiente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/admin');
    }

    public function test_protected_routes_redirect_guests_to_filament_login(): void
    {
        $response = $this->get('/mapa/clientes');

        $response->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_authenticated_users_can_open_the_map_page(): void
    {
        Permission::findOrCreate('tracking.view');

        $user = User::factory()->create();
        $user->givePermissionTo('tracking.view');

        $response = $this
            ->actingAs($user)
            ->get('/admin/mapa-clientes');

        $response
            ->assertOk()
            ->assertSee('Mapa de Clientes')
            ->assertSee('clients-map', false)
            ->assertSee('clients-list', false)
            ->assertSee('map-toggle-technicians', false)
            ->assertSee('map-technicians-panel', false)
            ->assertSee('build/assets/mapa-clientes', false)
            ->assertSee('leaflet', false);
    }

    public function test_authenticated_users_can_fetch_clients_for_the_map_with_active_pendientes(): void
    {
        Permission::findOrCreate('tracking.view');

        $user = User::factory()->create();
        $user->givePermissionTo('tracking.view');

        $clientWithCoordinates = Client::query()->create([
            'name' => 'Cliente Mapa',
            'address' => 'Calle 123',
            'city' => 'Buenos Aires',
            'state' => 'Buenos Aires',
            'postal_code' => '1000',
        ]);
        $clientWithCoordinates->forceFill([
            'latitud' => -34.6037,
            'longitud' => -58.3816,
        ])->save();

        $clientWithoutCoordinates = Client::query()->create([
            'name' => 'Cliente Sin Coordenadas',
        ]);

        Pendiente::query()->create([
            'client_id' => $clientWithCoordinates->id,
            'user_id' => $user->id,
            'type' => 'visita',
            'description' => 'Visita pendiente',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'alta',
            'due_date' => now()->addDay()->toDateString(),
        ]);

        Pendiente::query()->create([
            'client_id' => $clientWithCoordinates->id,
            'user_id' => $user->id,
            'type' => 'seguimiento',
            'description' => 'Seguimiento en proceso',
            'status' => Pendiente::STATUS_IN_PROGRESS,
            'priority' => 'media',
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        Pendiente::query()->create([
            'client_id' => $clientWithCoordinates->id,
            'user_id' => $user->id,
            'type' => 'archivo',
            'description' => 'Tarea finalizada',
            'status' => Pendiente::STATUS_COMPLETED,
            'priority' => 'baja',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson('/mapa/clientes');

        $response
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment([
                'id' => $clientWithCoordinates->id,
                'name' => 'Cliente Mapa',
                'has_coordinates' => true,
                'pending_count' => 1,
                'in_progress_count' => 1,
                'active_pendientes_count' => 2,
            ])
            ->assertJsonFragment([
                'description' => 'Visita pendiente',
                'status' => Pendiente::STATUS_PENDING,
                'status_label' => 'Pendiente',
            ])
            ->assertJsonFragment([
                'description' => 'Seguimiento en proceso',
                'status' => Pendiente::STATUS_IN_PROGRESS,
                'status_label' => 'En proceso',
            ])
            ->assertJsonFragment([
                'id' => $clientWithoutCoordinates->id,
                'name' => 'Cliente Sin Coordenadas',
                'has_coordinates' => false,
                'active_pendientes_count' => 0,
            ])
            ->assertJsonMissing([
                'description' => 'Tarea finalizada',
            ]);
    }
}
