<?php

namespace App\Filament\Resources\UsedEquipment\RelationManagers;

use App\Models\UsedEquipment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    protected static ?string $title = 'Historial';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('movement_type')
                    ->label('Movimiento')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ingreso' => 'Ingreso',
                        'cambio_estado' => 'Cambio de estado',
                        'reubicacion' => 'Reubicacion',
                        'actualizacion_componentes' => 'Componentes',
                        'extraccion_pieza' => 'Extraccion de pieza',
                        'agregado_pieza' => 'Agregado de pieza',
                        'venta' => 'Venta',
                        default => str_replace('_', ' ', $state),
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('Detalle')
                    ->wrap(),
                Tables\Columns\TextColumn::make('to_status')
                    ->label('Estado')
                    ->formatStateUsing(fn (?string $state): ?string => $state ? (UsedEquipment::STATUS_OPTIONS[$state] ?? $state) : null),
                Tables\Columns\TextColumn::make('to_location')
                    ->label('Ubicacion')
                    ->wrap(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Responsable')
                    ->placeholder('Sistema'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
