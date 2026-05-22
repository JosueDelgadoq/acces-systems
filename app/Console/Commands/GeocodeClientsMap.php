<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\GeolocalizacionService;
use Illuminate\Console\Command;

class GeocodeClientsMap extends Command
{
    protected $signature = 'clients:geocode-map
        {--limit=0 : Cantidad maxima de clientes a procesar}
        {--refresh : Reprocesa aunque el cliente ya tenga coordenadas}
        {--client_id=* : Procesa solo IDs puntuales}';

    protected $description = 'Geocodifica direcciones de clientes para el mapa operativo.';

    public function handle(GeolocalizacionService $geolocalizacionService): int
    {
        $limit = max(0, (int) $this->option('limit'));
        $refresh = (bool) $this->option('refresh');
        $clientIds = collect((array) $this->option('client_id'))
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->values();

        $query = Client::query()
            ->select([
                'id',
                'name',
                'address',
                'city',
                'state',
                'postal_code',
                'latitud',
                'longitud',
                'direccion_normalizada',
            ])
            ->whereNotNull('address')
            ->orderBy('id');

        if ($clientIds->isNotEmpty()) {
            $query->whereIn('id', $clientIds->all());
        }

        if (! $refresh) {
            $query->where(function ($inner): void {
                $inner
                    ->whereNull('latitud')
                    ->orWhereNull('longitud')
                    ->orWhere('latitud', 0)
                    ->orWhere('longitud', 0);
            });
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $clients = $query->get();

        if ($clients->isEmpty()) {
            $this->info('No hay clientes pendientes para geocodificar.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Procesando %d cliente(s)...', $clients->count()));

        $updated = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($clients->count());
        $bar->start();

        foreach ($clients as $client) {
            $result = $geolocalizacionService->geocodeClientAddress(
                $client->address,
                $client->city,
                $client->state,
                $client->postal_code,
            );

            if ($result === null) {
                $failed++;
                $bar->advance();
                usleep(1100000);
                continue;
            }

            $client->forceFill([
                'latitud' => $result['latitud'],
                'longitud' => $result['longitud'],
                'direccion_normalizada' => $result['direccion_formateada'],
            ])->save();

            $updated++;
            $bar->advance();
            usleep(1100000);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Geocodificados: {$updated}");
        $this->line("Sin resultado: {$failed}");

        return self::SUCCESS;
    }
}
