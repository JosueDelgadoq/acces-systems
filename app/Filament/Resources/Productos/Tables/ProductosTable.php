<?php

namespace App\Filament\Resources\Productos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->badge()
                    ->sortable(),
                TextColumn::make('nombre')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand')
                    ->label('Marca')
                    ->placeholder('Sin marca')
                    ->toggleable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->placeholder('Sin SKU')
                    ->toggleable(),
                TextColumn::make('tipo')
                    ->badge()
                    ->searchable(),
                TextColumn::make('tracking_mode_label')
                    ->label('Seguimiento')
                    ->badge(),
                TextColumn::make('variantes_count')
                    ->counts('variantes')
                    ->label('Variantes')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
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
