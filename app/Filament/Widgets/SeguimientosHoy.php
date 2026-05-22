<?php

namespace App\Filament\Widgets;

use App\Models\Seguimiento;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class SeguimientosHoy extends BaseWidget
{
    protected static ?string $heading = 'Seguimientos de hoy';

    protected int | string | array $columnSpan = 8;

    protected ?string $extraAttributes = 'p-2';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Seguimiento::query()
                    ->with(['lead:id,nombre'])
                    ->where('estado', 'pendiente')
                    ->whereDate('fecha_proxima_accion', now())
            )
            ->columns([
                Tables\Columns\TextColumn::make('lead.nombre')
                    ->label('Cliente'),
                Tables\Columns\TextColumn::make('proxima_accion')
                    ->label('Accion'),
                Tables\Columns\TextColumn::make('fecha_proxima_accion')
                    ->dateTime()
                    ->label('Fecha')
                    ->color(fn ($record) => $record->fecha_proxima_accion < now() ? 'danger' : 'warning'),
            ]);
    }
}
