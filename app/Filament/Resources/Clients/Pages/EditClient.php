<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use App\Services\GeolocalizacionService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('geocode')
                ->label('Geocodificar')
                ->icon('heroicon-o-map-pin')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Geocodificar cliente')
                ->modalDescription('Se intentara obtener latitud y longitud usando la direccion cargada del cliente.')
                ->action(function (GeolocalizacionService $geolocalizacionService): void {
                    /** @var Client $client */
                    $client = $this->getRecord();

                    $result = $geolocalizacionService->geocodeClientAddress(
                        $client->address,
                        $client->city,
                        $client->state,
                        $client->postal_code,
                    );

                    if ($result === null) {
                        Notification::make()
                            ->title('No se pudo geocodificar')
                            ->body('Revisa la direccion antes de volver a intentar.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $client->forceFill([
                        'latitud' => $result['latitud'],
                        'longitud' => $result['longitud'],
                        'direccion_normalizada' => $result['direccion_formateada'],
                    ])->save();

                    Notification::make()
                        ->title('Cliente geocodificado')
                        ->body('Se actualizaron las coordenadas y la direccion normalizada del cliente.')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'address',
                        'city',
                        'state',
                        'postal_code',
                    ]);
                }),
            DeleteAction::make(),
        ];
    }
}
