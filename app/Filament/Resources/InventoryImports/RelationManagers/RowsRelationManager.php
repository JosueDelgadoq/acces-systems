<?php

namespace App\Filament\Resources\InventoryImports\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RowsRelationManager extends RelationManager
{
    protected static string $relationship = 'rows';

    protected static ?string $title = 'Filas procesadas';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('source_sheet')
                    ->label('Hoja')
                    ->sortable(),
                Tables\Columns\TextColumn::make('source_row_number')
                    ->label('Fila')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_label')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Producto')
                    ->getStateUsing(fn ($record): ?string => data_get($record->normalized_payload, 'product_name'))
                    ->wrap(),
                Tables\Columns\TextColumn::make('inventory_code')
                    ->label('Código')
                    ->getStateUsing(fn ($record): ?string => data_get($record->normalized_payload, 'inventory_code'))
                    ->placeholder('Auto'),
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('Serie')
                    ->getStateUsing(fn ($record): ?string => data_get($record->normalized_payload, 'serial_number'))
                    ->placeholder('Sin serie'),
                Tables\Columns\TextColumn::make('validation_errors_text')
                    ->label('Errores')
                    ->getStateUsing(fn ($record): string => collect($record->validation_errors ?? [])->join(' | '))
                    ->wrap(),
                Tables\Columns\TextColumn::make('conflict_details_text')
                    ->label('Conflictos')
                    ->getStateUsing(fn ($record): string => collect($record->conflict_details ?? [])->join(' | '))
                    ->wrap(),
            ])
            ->defaultSort('source_row_number')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
