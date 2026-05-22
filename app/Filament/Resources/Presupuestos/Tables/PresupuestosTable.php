<?php

namespace App\Filament\Resources\Presupuestos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PresupuestosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lead.crm_id')
                    ->label('CRM')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lead.cliente')
                    ->label('Cliente')
                    ->searchable(),
                TextColumn::make('presupuesto_definitivo')
                    ->label('Definitivo')
                    ->money('ARS')
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total items')
                    ->money('ARS'),
                TextColumn::make('fecha_envio')
                    ->label('Fecha de envío')
                    ->date()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Creado por')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
