<?php

namespace App\Filament\Resources\Ventas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VentasTable
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
                TextColumn::make('producto_instalado')
                    ->label('Producto instalado')
                    ->searchable(),
                TextColumn::make('monto_total')
                    ->label('Monto total')
                    ->money('ARS')
                    ->sortable(),
                TextColumn::make('estado')
                    ->badge(),
                TextColumn::make('fecha_cierre')
                    ->label('Fecha de cierre')
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
