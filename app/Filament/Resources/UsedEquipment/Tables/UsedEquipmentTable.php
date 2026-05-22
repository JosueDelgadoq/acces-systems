<?php

namespace App\Filament\Resources\UsedEquipment\Tables;

use App\Models\UsedEquipment;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsedEquipmentTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Codigo')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('brand_label')
                    ->label('Marca')
                    ->badge()
                    ->placeholder('Sin definir'),
                TextColumn::make('type_label')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('ingress_condition_label')
                    ->label('Ingreso')
                    ->placeholder('Sin definir')
                    ->wrap(),
                TextColumn::make('status_label')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Completa' => 'success',
                        'A revisar' => 'warning',
                        'Repuestos' => 'danger',
                        'Vendida' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('full_location')
                    ->label('Ubicacion')
                    ->placeholder('Sin definir')
                    ->wrap(),
                TagsColumn::make('missing_parts_labels')
                    ->label('Faltantes')
                    ->getStateUsing(fn (UsedEquipment $record): array => $record->missing_parts_labels ?: ['Sin faltantes'])
                    ->color(fn (UsedEquipment $record): string => empty($record->missing_parts) ? 'success' : 'danger'),
                TextColumn::make('movements_count')
                    ->counts('movements')
                    ->label('Mov.')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                SelectFilter::make('type')->options(UsedEquipment::TYPE_OPTIONS),
                SelectFilter::make('brand')->options(UsedEquipment::BRAND_OPTIONS),
                SelectFilter::make('status')->options(UsedEquipment::STATUS_OPTIONS),
                SelectFilter::make('warehouse_zone')
                    ->label('Zona')
                    ->options(UsedEquipment::WAREHOUSE_ZONE_OPTIONS),
            ])
            ->actions([
                Action::make('registrar_movimiento')
                    ->label('Movimiento')
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
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (UsedEquipment $record, array $data): void {
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
                Action::make('a_venta')
                    ->label('Venta')
                    ->color('success')
                    ->visible(fn (UsedEquipment $record): bool => $record->status !== 'completa')
                    ->action(function (UsedEquipment $record): void {
                        $record->status = 'completa';
                        $record->save();
                    }),
                Action::make('a_revision')
                    ->label('Revisar')
                    ->color('warning')
                    ->visible(fn (UsedEquipment $record): bool => $record->status !== 'a_revisar')
                    ->action(function (UsedEquipment $record): void {
                        $record->status = 'a_revisar';
                        $record->save();
                    }),
                Action::make('a_repuestos')
                    ->label('Repuestos')
                    ->color('danger')
                    ->visible(fn (UsedEquipment $record): bool => $record->status !== 'repuestos')
                    ->action(function (UsedEquipment $record): void {
                        $record->status = 'repuestos';
                        $record->save();
                    }),
                Action::make('ficha')
                    ->label('Ficha')
                    ->icon('heroicon-o-eye')
                    ->url(fn (UsedEquipment $record): string => $record->public_url, shouldOpenInNewTab: true),
                Action::make('etiqueta')
                    ->label('Etiqueta')
                    ->icon('heroicon-o-tag')
                    ->url(fn (UsedEquipment $record): string => $record->label_url, shouldOpenInNewTab: true),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
