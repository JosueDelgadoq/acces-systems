<?php

namespace Tests\Feature;

use App\Services\GeolocalizacionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeolocalizacionServiceTest extends TestCase
{
    public function test_it_geocodes_using_full_address_context(): void
    {
        Cache::flush();

        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '-34.6992095',
                    'lon' => '-58.3324447',
                    'display_name' => 'Luis Jose de Chorroarin 2046, Monte Chingolo, Buenos Aires, Argentina',
                ],
            ], 200),
        ]);

        $service = app(GeolocalizacionService::class);

        $result = $service->geocodeClientAddress(
            'Luis Jose de Chorroarin 2046',
            'Monte Chingolo',
            'Provincia de Buenos Aires',
            'B1825EAL',
        );

        $this->assertNotNull($result);
        $this->assertSame(-34.6992095, $result['latitud']);
        $this->assertSame(-58.3324447, $result['longitud']);

        Http::assertSent(function ($request): bool {
            return str_starts_with($request->url(), 'https://nominatim.openstreetmap.org/search')
                && $request['q'] === 'Luis Jose de Chorroarin 2046, Monte Chingolo, Provincia de Buenos Aires, B1825EAL, Argentina';
        });
    }
}
