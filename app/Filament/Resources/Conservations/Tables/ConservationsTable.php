<?php

namespace App\Filament\Resources\Conservations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;

class ConservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Inicio contrato')
                    ->date()
                    ->sortable(),

                TextColumn::make('next_service_date')
                    ->label('Próximo servicio')
                    ->date()
                    ->sortable(),

                TextColumn::make('expiration_date')
                    ->label('Vencimiento')
                    ->date()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) =>
                        $record->expiration_date < now()
                            ? 'danger'
                            : ($record->expiration_date < now()->addDays(30)
                                ? 'warning'
                                : 'success')
                    ),

                TextColumn::make('frequency')
                    ->label('Frecuencia')
                    ->badge(),

            ])

            ->filters([

                Filter::make('vencidos')
                    ->label('Vencidos')
                    ->query(fn ($query) =>
                        $query->where('expiration_date', '<', now())
                    ),

                Filter::make('por_vencer')
                    ->label('Por vencer (30 días)')
                    ->query(fn ($query) =>
                        $query->whereBetween(
                            'expiration_date',
                            [now(), now()->addDays(30)]
                        )
                    ),

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