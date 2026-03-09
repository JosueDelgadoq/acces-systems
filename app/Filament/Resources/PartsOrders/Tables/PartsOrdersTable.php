<?php

namespace App\Filament\Resources\PartsOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PartsOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('technician.name')
                    ->label('Técnico')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('part_name')
                    ->label('Repuesto')
                    ->searchable(),
                TextColumn::make('supplier')
                    ->label('Proveedor')
                    ->searchable(),
                TextColumn::make('estimated_cost')
                    ->label('Costo Est.')
                    ->money('ARS')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente_cotizacion' => 'gray',
                        'cotizado' => 'info',
                        'aprobado' => 'warning',
                        'comprado' => 'warning',
                        'recibido' => 'success',
                        'entregado_tecnico' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('purchase_date')
                    ->label('Fecha Compra')
                    ->date()
                    ->sortable(),
                TextColumn::make('arrival_date')
                    ->label('Fecha Llega')
                    ->date()
                    ->sortable(),
                TextColumn::make('invoice')
                    ->label('Factura')
                    ->searchable(),
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

