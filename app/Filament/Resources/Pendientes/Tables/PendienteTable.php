<?php

namespace App\Filament\Resources\Pendientes\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Services\PendientesService;

class PendienteTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->records(fn () => PendientesService::get()) // 🔥 MAGIA ACÁ
            ->recordKey('id')
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Reclamo' => 'danger',
                        'Conservación' => 'warning',
                        default => 'secondary',
                    }),

                TextColumn::make('client')
                    ->label('Cliente')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Descripción')
                    ->wrap(),

                TextColumn::make('due_date')
                    ->label('Fecha')
                    ->dateTime(),

                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'alta' => 'danger',
                        'media' => 'warning',
                        'baja' => 'success',
                        default => 'secondary',
                    }),
            ]);
    }
}