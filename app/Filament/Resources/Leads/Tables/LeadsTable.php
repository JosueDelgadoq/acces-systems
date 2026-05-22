<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Models\Lead;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fecha_ingreso', 'desc')
            ->columns([
                TextColumn::make('crm_id')
                    ->label('ID CRM')
                    ->weight(FontWeight::Bold)
                    ->sortable()
                    ->searchable(),

                TextColumn::make('cliente')
                    ->label('Cliente')
                    ->state(fn (Lead $record): string => $record->cliente)
                    ->description(fn (Lead $record): string => trim(($record->telefono ?: 'Sin telefono') . ' - ' . ($record->email ?: 'Sin email')))
                    ->searchable(['nombre', 'apellido', 'telefono', 'email']),

                TextColumn::make('comercialAsignado.name')
                    ->label('Comercial')
                    ->placeholder('Sin asignar')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('estado_pipeline')
                    ->label('Pipeline')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Lead::getPipelineLabel($state))
                    ->color(fn (?string $state): string => Lead::getPipelineColor($state))
                    ->sortable(),

                TextColumn::make('nextPendingSeguimiento.proxima_accion')
                    ->label('Proxima accion')
                    ->placeholder('Sin accion programada')
                    ->wrap()
                    ->limit(50),

                TextColumn::make('nextPendingSeguimiento.fecha_proxima_accion')
                    ->label('Compromiso')
                    ->date('d/m/Y')
                    ->placeholder('Sin fecha')
                    ->color(function ($state, Lead $record): string {
                        if (! $record->nextPendingSeguimiento?->fecha_proxima_accion) {
                            return 'gray';
                        }

                        return $record->nextPendingSeguimiento->fecha_proxima_accion->isPast() ? 'danger' : 'warning';
                    })
                    ->sortable(),

                TextColumn::make('fecha_ultimo_seguimiento')
                    ->label('Ultima gestion')
                    ->date('d/m/Y')
                    ->placeholder('Sin registrar')
                    ->sortable(),

                TextColumn::make('estado_semaforo')
                    ->label('Ritmo')
                    ->formatStateUsing(fn (Lead $record): string => match ($record->estado_semaforo) {
                        'urgente' => 'Urgente',
                        'atencion' => 'Atencion',
                        default => 'Al dia',
                    })
                    ->badge()
                    ->color(fn (Lead $record): string => match ($record->estado_semaforo) {
                        'urgente' => 'danger',
                        'atencion' => 'warning',
                        default => 'success',
                    }),
            ])
            ->filters([
                SelectFilter::make('estado_pipeline')
                    ->label('Pipeline')
                    ->options(Lead::getPipelineOptions()),

                SelectFilter::make('comercial_asignado_id')
                    ->label('Comercial')
                    ->relationship('comercialAsignado', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('solo_abiertos')
                    ->label('Solo abiertos')
                    ->query(fn ($query) => $query->openPipeline()),

                Filter::make('sin_gestion_reciente')
                    ->label('Sin gestion reciente')
                    ->query(fn ($query) => $query->whereDate('fecha_ultimo_seguimiento', '<', now()->subDays(3))),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
