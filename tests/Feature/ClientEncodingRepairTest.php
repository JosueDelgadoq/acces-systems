<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClientEncodingRepairTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_save_repairs_common_mojibake_on_text_fields(): void
    {
        $client = Client::query()->create([
            'name' => "Ara\u{00C3}\u{00BA}z",
            'company' => "Fran\u{00C3}\u{00A7}a Alarmas",
            'address' => "Direcci\u{00C3}\u{00B3}n 123",
            'city' => "Luj\u{00C3}\u{00A1}n",
            'notes' => "Instalaci\u{00C3}\u{00B3}n pendiente",
        ]);

        $client->refresh();

        $this->assertSame('Araúz', $client->name);
        $this->assertSame('França Alarmas', $client->company);
        $this->assertSame('Dirección 123', $client->address);
        $this->assertSame('Luján', $client->city);
        $this->assertSame('Instalación pendiente', $client->notes);
    }

    public function test_client_save_keeps_valid_utf8_text_unchanged(): void
    {
        $client = Client::query()->create([
            'name' => 'José Álvarez',
            'company' => 'São Paulo Equipos',
            'address' => 'Peña 742',
            'city' => 'Cañuelas',
            'notes' => 'Revisión técnica',
        ]);

        $client->refresh();

        $this->assertSame('José Álvarez', $client->name);
        $this->assertSame('São Paulo Equipos', $client->company);
        $this->assertSame('Peña 742', $client->address);
        $this->assertSame('Cañuelas', $client->city);
        $this->assertSame('Revisión técnica', $client->notes);
    }

    public function test_repair_command_can_preview_and_fix_existing_corrupted_rows(): void
    {
        DB::table('clients')->insert([
            'name' => "Ara\u{00C3}\u{00BA}z",
            'company' => "Fran\u{00C3}\u{00A7}a Alarmas",
            'address' => "Direcci\u{00C3}\u{00B3}n 123",
            'city' => "Luj\u{00C3}\u{00A1}n",
            'notes' => "Instalaci\u{00C3}\u{00B3}n pendiente",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('clients:repair-encoding', ['--dry-run' => true])
            ->assertExitCode(0);

        $previewClient = Client::query()->firstOrFail();

        $this->assertSame("Ara\u{00C3}\u{00BA}z", $previewClient->name);
        $this->assertSame("Fran\u{00C3}\u{00A7}a Alarmas", $previewClient->company);

        $this->artisan('clients:repair-encoding')
            ->assertExitCode(0);

        $repairedClient = Client::query()->firstOrFail();

        $this->assertSame('Araúz', $repairedClient->name);
        $this->assertSame('França Alarmas', $repairedClient->company);
        $this->assertSame('Dirección 123', $repairedClient->address);
        $this->assertSame('Luján', $repairedClient->city);
        $this->assertSame('Instalación pendiente', $repairedClient->notes);
    }
}
