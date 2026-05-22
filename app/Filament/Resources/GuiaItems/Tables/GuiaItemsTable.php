<?php

namespace App\Filament\Resources\GuiaItems\Tables;

use App\Models\ProductoVariante;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class GuiaItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('productoVariante.display_name')
                    ->label('Variante')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('longitud')
                    ->label('Longitud')
                    ->sortable(),

                IconColumn::make('tiene_angulo')
                    ->label('Ángulo')
                    ->boolean(),

                IconColumn::make('tiene_patas')
                    ->label('Patas')
                    ->boolean(),

                IconColumn::make('rebatible')
                    ->label('Rebatible')
                    ->boolean(),

                TextColumn::make('estado')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('producto_variante_id')
                    ->label('Variante')
                    ->relationship(
                        name: 'productoVariante',
                        titleAttribute: 'id',
                        modifyQueryUsing: fn ($query) => $query
                            ->with('producto')
                            ->whereHas('producto', fn ($productQuery) => $productQuery->where('tipo', 'guia'))
                    )
                    ->getOptionLabelFromRecordUsing(fn (ProductoVariante $record) => $record->display_name),

                TernaryFilter::make('tiene_angulo')
                    ->label('Con ángulo'),

                TernaryFilter::make('tiene_patas')
                    ->label('Con patas'),
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
