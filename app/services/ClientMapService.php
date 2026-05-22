<?php

namespace App\Services;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Pendientes\PendienteResource;
use App\Models\Client;
use App\Models\Pendiente;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ClientMapService
{
    protected const TYPE_CONSERVATION = 'conservation';
    protected const TYPE_OPERATIONAL = 'operational';
    protected const TYPE_STANDARD = 'standard';

    public function buildPayload(): Collection
    {
        return Client::query()
            ->select([
                'id',
                'name',
                'company',
                'city',
                'state',
                'postal_code',
                'address',
                'latitud',
                'longitud',
                'direccion_normalizada',
                'installation_date',
            ])
            ->with([
                'pendientes' => fn ($query) => $query
                    ->select([
                        'id',
                        'client_id',
                        'description',
                        'status',
                        'priority',
                        'due_date',
                        'notes',
                    ])
                    ->whereIn('status', [Pendiente::STATUS_PENDING, Pendiente::STATUS_IN_PROGRESS])
                    ->orderBy('due_date'),
            ])
            ->withCount([
                'conservations',
                'conservations as active_conservations_count' => fn ($query) => $query
                    ->activeContracts(),
                'claims',
                'habilitations',
                'technicalBudgets',
                'equipmentDeliveries',
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Client $client): array => $this->mapClient($client))
            ->values();
    }

    protected function mapClient(Client $client): array
    {
        $formattedAddress = $client->full_address;
        $hasAddressData = filled($client->address)
            || filled($client->city)
            || filled($client->state)
            || filled($client->postal_code);

        $activePendientes = $client->pendientes->map(function (Pendiente $pendiente): array {
            return [
                'id' => $pendiente->id,
                'description' => $pendiente->description,
                'status' => $pendiente->status,
                'status_label' => Pendiente::getStatusLabel($pendiente->status),
                'priority' => $pendiente->priority,
                'due_date' => $this->formatDate($pendiente->due_date),
                'notes' => $pendiente->notes,
                'url' => PendienteResource::getUrl('edit', ['record' => $pendiente]),
            ];
        })->values();

        $lat = $client->latitud !== null ? (float) $client->latitud : null;
        $lng = $client->longitud !== null ? (float) $client->longitud : null;
        $coordinatesAreValid = $this->coordinatesAreValid($lat, $lng);
        $coordinatesQuality = $this->resolveCoordinatesQuality($client, $coordinatesAreValid);
        $clientType = $this->resolveClientType($client, $activePendientes->count());
        $typeMeta = $this->typeMeta($clientType);
        $inProgressCount = $activePendientes->where('status', Pendiente::STATUS_IN_PROGRESS)->count();
        $pendingCount = $activePendientes->where('status', Pendiente::STATUS_PENDING)->count();

        return [
            'id' => $client->id,
            'name' => $client->name,
            'company' => $client->company,
            'city' => $client->city,
            'state' => $client->state,
            'address' => $client->address,
            'full_address' => $formattedAddress,
            'normalized_address' => $client->direccion_normalizada,
            'latitud' => $coordinatesAreValid ? $lat : null,
            'longitud' => $coordinatesAreValid ? $lng : null,
            'raw_latitud' => $lat,
            'raw_longitud' => $lng,
            'has_coordinates' => $coordinatesAreValid,
            'has_address_data' => $hasAddressData,
            'coordinates_quality' => $coordinatesQuality['key'],
            'coordinates_quality_label' => $coordinatesQuality['label'],
            'coordinates_quality_hint' => $coordinatesQuality['hint'],
            'url' => ClientResource::getUrl('edit', ['record' => $client]),
            'active_pendientes_count' => $activePendientes->count(),
            'pending_count' => $pendingCount,
            'in_progress_count' => $inProgressCount,
            'conservations_count' => (int) $client->conservations_count,
            'active_conservations_count' => (int) $client->active_conservations_count,
            'claims_count' => (int) $client->claims_count,
            'habilitations_count' => (int) $client->habilitations_count,
            'technical_budgets_count' => (int) $client->technical_budgets_count,
            'equipment_deliveries_count' => (int) $client->equipment_deliveries_count,
            'client_type' => $clientType,
            'client_type_label' => $typeMeta['label'],
            'client_type_color' => $typeMeta['color'],
            'client_type_description' => $typeMeta['description'],
            'installation_date' => $this->formatDate($client->installation_date),
            'map_status' => $inProgressCount > 0
                ? Pendiente::STATUS_IN_PROGRESS
                : ($pendingCount > 0 ? Pendiente::STATUS_PENDING : ($coordinatesAreValid ? 'located' : 'not_located')),
            'active_pendientes' => $activePendientes,
        ];
    }

    protected function resolveClientType(Client $client, int $activePendientesCount): string
    {
        if ((int) $client->active_conservations_count > 0 || (int) $client->conservations_count > 0) {
            return self::TYPE_CONSERVATION;
        }

        if ($activePendientesCount > 0 || (int) $client->claims_count > 0 || (int) $client->habilitations_count > 0) {
            return self::TYPE_OPERATIONAL;
        }

        return self::TYPE_STANDARD;
    }

    protected function typeMeta(string $type): array
    {
        return match ($type) {
            self::TYPE_CONSERVATION => [
                'label' => 'Conservacion',
                'color' => '#f97316',
                'description' => 'Cliente con conservaciones activas o historicas.',
            ],
            self::TYPE_OPERATIONAL => [
                'label' => 'Operativo',
                'color' => '#0f766e',
                'description' => 'Cliente con reclamos, pendientes o gestiones operativas.',
            ],
            default => [
                'label' => 'General',
                'color' => '#2563eb',
                'description' => 'Cliente general sin conservacion activa.',
            ],
        };
    }

    protected function resolveCoordinatesQuality(Client $client, bool $coordinatesAreValid): array
    {
        if (! $coordinatesAreValid) {
            return [
                'key' => 'missing',
                'label' => 'Sin coordenadas',
                'hint' => 'No hay latitud/longitud valida cargada para este cliente.',
            ];
        }

        $normalized = trim((string) $client->direccion_normalizada);
        $fullAddress = trim((string) $client->full_address);

        if ($normalized !== '' && str_contains(mb_strtolower($normalized), mb_strtolower((string) $client->city))) {
            return [
                'key' => 'verified',
                'label' => 'Geocodificada',
                'hint' => 'Coordenadas obtenidas con direccion normalizada.',
            ];
        }

        if ($fullAddress !== '' && filled($client->city)) {
            return [
                'key' => 'approximate',
                'label' => 'Aproximada',
                'hint' => 'Conviene revisar la direccion para mejorar precision.',
            ];
        }

        return [
            'key' => 'basic',
            'label' => 'Basica',
            'hint' => 'La direccion tiene pocos datos; la ubicacion puede ser imprecisa.',
        ];
    }

    protected function coordinatesAreValid(?float $lat, ?float $lng): bool
    {
        if ($lat === null || $lng === null) {
            return false;
        }

        if ($lat === 0.0 && $lng === 0.0) {
            return false;
        }

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return false;
        }

        return true;
    }

    protected function formatDate(mixed $date): ?string
    {
        if (! $date instanceof CarbonInterface) {
            return null;
        }

        return $date->format('d/m/Y');
    }
}
