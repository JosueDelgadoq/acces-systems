<?php

namespace App\Filament\Resources\GuiaItems\Pages;

use App\Filament\Resources\GuiaItems\GuiaItemResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class ListGuiaItems extends ListRecords
{
    protected static string $resource = GuiaItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reimportar_guias')
                ->label('Reimportar lista')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Reimportar guías')
                ->modalDescription('Se eliminarán las guías actuales y se volverán a cargar desde la lista definida en el sistema.')
                ->action(function (): void {
                    try {
                        Artisan::call('guias:importar', ['--reset' => true]);

                        Notification::make()
                            ->title('Importación completada')
                            ->body(trim(Artisan::output()) ?: 'Las guías se reimportaron correctamente.')
                            ->success()
                            ->send();

                        $this->redirect(static::getUrl(), navigate: true);
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Falló la importación')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            CreateAction::make(),
        ];
    }
}
