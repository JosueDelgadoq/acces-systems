<?php

namespace App\Filament\Resources\UsedEquipment\Pages;

use App\Filament\Resources\UsedEquipment\UsedEquipmentResource;
use App\Models\Repuesto;
use App\Models\UsedEquipment;
use App\Services\EquipoService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUsedEquipment extends EditRecord
{
    protected static string $resource = UsedEquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('registrar_movimiento')
                ->label('Registrar movimiento')
                ->icon('heroicon-o-clipboard-document-list')
                ->form([
                    Forms\Components\Select::make('movement_type')
                        ->label('Tipo de movimiento')
                        ->options([
                            'extraccion_pieza' => 'Extraccion de pieza',
                            'agregado_pieza' => 'Agregado de pieza',
                            'venta' => 'Venta',
                            'reubicacion' => 'Reubicacion',
                            'cambio_estado' => 'Cambio de estado',
                            'observacion' => 'Observacion',
                        ])
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->label('Nuevo estado')
                        ->options(UsedEquipment::STATUS_OPTIONS),
                    Forms\Components\Select::make('warehouse_zone')
                        ->label('Zona')
                        ->options(UsedEquipment::WAREHOUSE_ZONE_OPTIONS),
                    Forms\Components\TextInput::make('location')->label('Ubicacion'),
                    Forms\Components\CheckboxList::make('missing_parts')
                        ->label('Faltantes actualizados')
                        ->options(UsedEquipment::MISSING_PART_OPTIONS)
                        ->columns(2),
                    Forms\Components\Textarea::make('description')
                        ->label('Detalle')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $record = $this->record;
                    $beforeStatus = $record->status;
                    $beforeLocation = $record->full_location;
                    $beforeParts = $record->missing_parts ?? [];

                    $record->fill([
                        'status' => $data['movement_type'] === 'venta'
                            ? 'vendida'
                            : ($data['status'] ?: $record->status),
                        'warehouse_zone' => $data['warehouse_zone'] ?: $record->warehouse_zone,
                        'location' => $data['location'] ?: $record->location,
                        'missing_parts' => $data['missing_parts'] ?? $record->missing_parts,
                    ]);
                    $record->save();

                    $record->recordMovement($data['movement_type'], [
                        'description' => $data['description'],
                        'from_status' => $beforeStatus,
                        'to_status' => $record->status,
                        'from_location' => $beforeLocation,
                        'to_location' => $record->full_location,
                        'parts_before' => $beforeParts,
                        'parts_after' => $record->missing_parts,
                    ]);
                }),
            Action::make('etiqueta')
                ->label('Ver etiqueta')
                ->icon('heroicon-o-tag')
                ->url(fn (): string => $this->record->label_url, shouldOpenInNewTab: true),
            Action::make('ficha')
                ->label('Ver ficha')
                ->icon('heroicon-o-eye')
                ->url(fn (): string => $this->record->public_url, shouldOpenInNewTab: true),
            Action::make('diagnosticar')
                ->label('Diagnosticar equipo')
                ->icon('heroicon-o-wrench-screwdriver')
                ->action(function () {
                    $service = new EquipoService();
                    $diagnostico = $service->diagnosticar($this->record->id);
                    $sugerencias = $service->sugerirRepuestos($diagnostico['faltantes']);

                    $mensaje = '';

                    foreach ($sugerencias as $s) {
                        $repuesto = Repuesto::find($s['repuesto_id']);
                        $mensaje .= "{$repuesto->nombre} - ";
                        $mensaje .= "Falta: {$s['necesario']} | ";
                        $mensaje .= "Stock: {$s['disponible']}\n";
                    }

                    if ($mensaje === '') {
                        $mensaje = 'Equipo completo';
                    }

                    Notification::make()
                        ->title('Diagnostico del equipo')
                        ->body($mensaje)
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
