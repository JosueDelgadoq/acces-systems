<?php

namespace App\Filament\Resources\BillingControls\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BillingControlsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('service_description')
                    ->label('Servicio')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('ARS')
                    ->sortable(),
                TextColumn::make('invoice_number')
                    ->label('N° Factura')
                    ->searchable(),
                TextColumn::make('invoice_date')
                    ->label('Fecha Factura')
                    ->date()
                    ->sortable(),
                TextColumn::make('invoiced')
                    ->label('Facturado')
                    ->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                TextColumn::make('paid')
                    ->label('Cobrado')
                    ->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                TextColumn::make('payment_date')
                    ->label('Fecha Cobro')
                    ->date()
                    ->sortable(),
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

