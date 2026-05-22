<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeolocalizacionService
{
    public function geocodeClientAddress(
        ?string $address,
        ?string $city = null,
        ?string $state = null,
        ?string $postalCode = null
    ): ?array {
        $candidates = collect([
            $this->buildCandidate([$address, $city, $state, $this->normalizePostalCode($postalCode), 'Argentina']),
            $this->buildCandidate([$address, $city, $state, 'Argentina']),
            $this->buildCandidate([$address, $city, 'Argentina']),
            $this->buildCandidate([$address, $state, 'Argentina']),
            $this->buildCandidate([$address, 'Argentina']),
            $this->buildCandidate([$address]),
        ])->filter()->unique()->values();

        foreach ($candidates as $candidate) {
            $result = $this->geocode($candidate);

            if ($result !== null) {
                return $result;
            }
        }

        Log::warning('No se pudo geocodificar la direccion del cliente.', [
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'postal_code' => $postalCode,
            'candidates' => $candidates->all(),
        ]);

        return null;
    }

    public function geocode(string $direccion): ?array
    {
        return Cache::remember("geo_" . md5($direccion), 86400, function () use ($direccion) {
            try {
                $response = Http::timeout(10)
                    ->retry(2, 500)
                    ->withHeaders([
                        'User-Agent' => config('app.name', 'ERP') . '/clientes-map',
                        'Accept-Language' => 'es-AR,es;q=0.9',
                    ])->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $direccion,
                        'format' => 'json',
                        'limit' => 1,
                        'addressdetails' => 1,
                        'countrycodes' => 'ar',
                    ]);
            } catch (ConnectionException $exception) {
                Log::warning('Error de conexion al geocodificar direccion.', [
                    'direccion' => $direccion,
                    'message' => $exception->getMessage(),
                ]);

                return null;
            }

            if (! $response->successful()) {
                Log::warning('El proveedor de geocodificacion devolvio un error.', [
                    'direccion' => $direccion,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 300),
                ]);

                return null;
            }

            $data = $response->json();

            if (empty($data)) {
                return null;
            }

            return [
                'latitud' => (float) $data[0]['lat'],
                'longitud' => (float) $data[0]['lon'],
                'direccion_formateada' => $data[0]['display_name'],
            ];
        });
    }

    protected function buildCandidate(array $parts): ?string
    {
        $value = collect($parts)
            ->map(fn ($part) => is_string($part) ? trim($part) : null)
            ->filter()
            ->implode(', ');

        return $value !== '' ? $value : null;
    }

    protected function normalizePostalCode(?string $postalCode): ?string
    {
        $value = is_string($postalCode) ? trim($postalCode) : null;

        if ($value === '') {
            return null;
        }

        return preg_replace('/\s+/', '', mb_strtoupper($value));
    }
}
