<?php

namespace App\Filament\Resources\ProductoUnidads\Pages;

use App\Filament\Resources\ProductoUnidads\ProductoUnidadResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\EquipoService;
use App\Models\Repuesto;

class EditProductoUnidad extends EditRecord
{
    protected static string $resource = ProductoUnidadResource::class;

protected function getHeaderActions(): array
{
    return [
        Action::make('diagnosticar')
            ->label('Diagnosticar equipo')
            ->icon('heroicon-o-wrench-screwdriver')
            ->action(function () {

                $service = new EquipoService();

                $diagnostico = $service->diagnosticar($this->record->id);

                $sugerencias = $service->sugerirRepuestos($diagnostico['faltantes']);

                // 🔥 Armamos mensaje
                $mensaje = '';

                foreach ($sugerencias as $s) {
                    $repuesto = Repuesto::find($s['repuesto_id']);

                    $mensaje .= "🔧 {$repuesto->nombre} - ";
                    $mensaje .= "Falta: {$s['necesario']} | ";
                    $mensaje .= "Stock: {$s['disponible']}\n";
                }

                if ($mensaje === '') {
                    $mensaje = '✅ Equipo completo';
                }

                Notification::make()
                    ->title('Diagnóstico del equipo')
                    ->body($mensaje)
                    ->success()
                    ->send();
            }),
    ];
}
}
