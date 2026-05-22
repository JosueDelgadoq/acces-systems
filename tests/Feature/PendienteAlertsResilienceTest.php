<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Pendiente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PendienteAlertsResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_can_be_created_even_if_alert_dispatch_store_table_is_missing(): void
    {
        Notification::fake();

        Schema::dropIfExists('alert_dispatches');

        $creator = User::factory()->create([
            'name' => 'Supervisor',
            'email' => 'supervisor@example.com',
        ]);

        $technician = User::factory()->create([
            'name' => 'Tecnico Resiliente',
            'email' => 'tecnico@example.com',
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Resiliente',
        ]);

        $this->actingAs($creator);

        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'user_id' => $technician->id,
            'type' => 'diagnostico',
            'description' => 'Alta de pendiente sin tabla de dedupe.',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'media',
        ]);

        $this->assertNotNull($pendiente->id);
        $this->assertDatabaseHas('pendientes', [
            'id' => $pendiente->id,
            'client_id' => $client->id,
            'user_id' => $technician->id,
        ]);
        $this->assertFalse(Schema::hasTable('alert_dispatches'));
    }
}
