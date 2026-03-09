<?php

namespace App\Filament\Resources\EquipmentDeliveries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EquipmentDeliveriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('equipment')
                    ->label('Equipo')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente_confirmacion' => 'gray',
                        'confirmado' => 'info',
                        'flete_solicitado' => 'warning',
                        'en_camino' => 'warning',
                        'entregado' => 'success',
                        'instalado' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('sale_date')
                    ->label('Fecha Venta')
                    ->date()
                    ->sortable(),
                TextColumn::make('delivery_date')
                    ->label('Fecha Entrega')
                    ->date()
                    ->sortable(),
                TextColumn::make('installation_date')
                    ->label('Fecha Instalación')
                    ->date()
                    ->sortable(),
                TextColumn::make('delivery_remito')
                    ->label('Remito Entrega')
                    ->searchable(),
                TextColumn::make('installation_remito')
                    ->label('Remito Instalación')
                    ->searchable(),
                TextColumn::make('signed_remito')
                    ->label('Firmado')
                    ->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

