<?php

namespace App\Filament\Resources\CommercialGoals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommercialGoalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('goal_month', 'desc')
            ->columns([
                TextColumn::make('goal_month')
                    ->label('Mes')
                    ->date('F Y')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Comercial')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('target_leads')
                    ->label('Leads')
                    ->sortable(),
                TextColumn::make('target_contacts')
                    ->label('Contactos')
                    ->sortable(),
                TextColumn::make('target_quotes')
                    ->label('Cotizaciones')
                    ->sortable(),
                TextColumn::make('target_sales')
                    ->label('Ventas')
                    ->sortable(),
                TextColumn::make('target_revenue')
                    ->label('Facturacion')
                    ->money('ARS')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->since()
                    ->sortable(),
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
