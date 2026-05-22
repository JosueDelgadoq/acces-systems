<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Support\TextEncodingNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RepairClientEncoding extends Command
{
    protected $signature = 'clients:repair-encoding
        {--client_id=* : Revisa solo IDs puntuales}
        {--dry-run : Muestra los cambios sin guardarlos}';

    protected $description = 'Repara texto mojibake comun en registros de clientes.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $clientIds = collect((array) $this->option('client_id'))
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->values();

        $query = Client::query()
            ->select(array_unique(['id', ...Client::NORMALIZABLE_TEXT_ATTRIBUTES]))
            ->orderBy('id');

        if ($clientIds->isNotEmpty()) {
            $query->whereIn('id', $clientIds->all());
        }

        $clients = $query->get();

        if ($clients->isEmpty()) {
            $this->info('No hay clientes para revisar.');

            return self::SUCCESS;
        }

        $changedClients = 0;
        $changedFields = 0;

        foreach ($clients as $client) {
            $updates = [];

            foreach (Client::NORMALIZABLE_TEXT_ATTRIBUTES as $attribute) {
                $original = $client->getAttribute($attribute);

                if (! is_string($original) || $original === '') {
                    continue;
                }

                $normalized = TextEncodingNormalizer::normalize($original);

                if ($normalized !== $original) {
                    $updates[$attribute] = $normalized;
                }
            }

            if ($updates === []) {
                continue;
            }

            $changedClients++;
            $changedFields += count($updates);

            $changes = collect($updates)
                ->map(fn ($value, $attribute): string => sprintf(
                    '%s: "%s" -> "%s"',
                    $attribute,
                    Str::limit((string) $client->getAttribute($attribute), 60, '...'),
                    Str::limit($value, 60, '...')
                ))
                ->implode('; ');

            $this->line(sprintf('Cliente #%d | %s', $client->id, $changes));

            if (! $dryRun) {
                $client->forceFill($updates)->saveQuietly();
            }
        }

        if ($changedClients === 0) {
            $this->info('No se detectaron textos con mojibake en clientes.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info(sprintf(
                'Simulación completada. Clientes con cambios: %d. Campos afectados: %d.',
                $changedClients,
                $changedFields,
            ));

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Reparación completada. Clientes actualizados: %d. Campos corregidos: %d.',
            $changedClients,
            $changedFields,
        ));

        return self::SUCCESS;
    }
}
