<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Models\Lead;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('crm_id')
                    ->label('ID CRM')
                    ->weight(FontWeight::Bold)
                    ->sortable()
                    ->searchable(),

                IconColumn::make('estado_semaforo')
                    ->label('Seguimiento')
                    ->icon(fn ($state) => match ($state) {
                        'urgente' => 'heroicon-m-exclamation-triangle',
                        'atencion' => 'heroicon-m-exclamation-circle',
                        'ok' => 'heroicon-m-check-circle',
                        default => 'heroicon-m-check-circle',
                    })
                    ->color(fn ($state) => match ($state) {
                        'urgente' => 'danger',
                        'atencion' => 'warning',
                        'ok' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('cliente')
                    ->label('Cliente')
                    ->state(fn (Lead $record) => $record->nombre . ' ' . $record->apellido)
                    ->searchable(['nombre', 'apellido'])
                    ->sortable(),

                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->url(fn ($record) => "https://wa.me/54".$record->telefono)
                    ->openUrlInNewTab()
                    ->icon('heroicon-m-chat-bubble-left-right'),

                TextColumn::make('estado_pipeline')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Ingresado' => 'gray',
                        'Contactado' => 'info',
                        'Orientacion dada' => 'success',
                        'Cotizacion enviada' => 'warning',
                        'Presupuesto definitivo enviado' => 'warning',
                        'Venta cerrada' => 'success',
                        'Perdido' => 'danger',
                        'Postergado' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('dias_sin_seguimiento')
                    ->label('Días sin seguimiento')
                    ->suffix(' días')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('fecha_ingreso')
                    ->label('Fecha ingreso')
                    ->date()
                    ->sortable(),

            ])

            ->filters([

                SelectFilter::make('estado_pipeline')
                    ->label('Estado')
                    ->options([
                        'Ingresado' => 'Ingresado',
                        'Contactado' => 'Contactado',
                        'Orientacion dada' => 'Orientación dada',
                        'Cotizacion enviada' => 'Cotización enviada',
                        'Presupuesto definitivo enviado' => 'Presupuesto definitivo enviado',
                        'Venta cerrada' => 'Venta cerrada',
                        'Perdido' => 'Perdido',
                        'Postergado' => 'Postergado',
                    ]),

                SelectFilter::make('canal_origen')
                    ->label('Origen')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'redes_sociales' => 'Redes Sociales',
                        'mail' => 'Mail',
                        'telefono' => 'Teléfono',
                    ]),

            ])

            ->defaultSort('fecha_ingreso', 'desc');
    }
}