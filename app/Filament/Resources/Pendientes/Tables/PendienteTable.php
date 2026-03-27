<?php

namespace App\Filament\Resources\Pendientes\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Carbon\Carbon;
use App\Filament\Resources\Clients\ClientResource;

class PendienteTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->sortable()
                    ->color(fn ($state) => match ($state) {
                        'reclamo' => 'danger',
                        'instalacion' => 'info',
                        'desinstalacion' => 'warning',
                        'soporte' => 'secondary',
                        default => 'gray',
                    }),

                    TextColumn::make('client.name')
                        ->label('Cliente')
                        ->sortable()
                        ->searchable()
                        ->url(fn ($record) => 
                            ClientResource::getUrl('edit', ['record' => $record->client_id])
                        )
                        ->openUrlInNewTab(false),

                TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(40)
                    ->wrap(),

                    TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i'),

                TextColumn::make('due_date')
                    ->label('Fecha programada')
                    ->formatStateUsing(function ($state) {
                    return $state 
                    ? \Carbon\Carbon::parse($state)->format('d/m/Y') 
                    : 'A programar';
                    }),

                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->sortable()
                    ->color(fn ($state) => match ($state) {
                        'alta' => 'danger',
                        'media' => 'warning',
                        'baja' => 'success',
                        default => 'gray',
                    }),

TextColumn::make('status')
    ->label('Estado')
    ->badge()
    ->sortable()

    ->formatStateUsing(function ($state, $record) {
        if (
            $record->status !== 'completed' &&
            $record->due_date &&
            Carbon::parse($record->due_date)->isBefore(Carbon::today())
        ) {
            return 'VENCIDO';
        }

        return match ($state) {
            'pending' => 'Pendiente',
            'in_progress' => 'En proceso',
            'completed' => 'Finalizado',
            'cancelled' => 'Cancelado',
            default => $state,
        };
    })

    ->color(function ($state, $record) {

        // 🔴 VENCIDO (antes de hoy)
        if (
            $record->status !== 'completed' &&
            $record->due_date &&
            \Carbon\Carbon::parse($record->due_date)->isBefore(\Carbon\Carbon::today())
        ) {
            return 'danger';
        }

        // 🟠 HOY
        if (
            $state === 'pending' &&
            $record->due_date &&
            \Carbon\Carbon::parse($record->due_date)->isToday()
        ) {
            return 'warning'; // naranja
        }

        // 🟡 PENDIENTE normal
        if ($state === 'pending') {
            return 'yellow';
        }

        // 🟢 COMPLETADO
        if ($state === 'completed') {
            return 'success';
        }

        // 🔵 EN PROCESO
        if ($state === 'in_progress') {
            return 'info';
        }

        // ⚪ CANCELADO
        if ($state === 'cancelled') {
            return 'gray';
        }

        return 'secondary';
    }),

                TextColumn::make('user.name')
                    ->label('Técnico'),
            ])

            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'in_progress' => 'En proceso',
                        'completed' => 'Finalizado',
                        'cancelled' => 'Cancelado',
                    ]),

                SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'alta' => 'Alta',
                        'media' => 'Media',
                        'baja' => 'Baja',
                    ]),

                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'reclamo' => 'Reclamo',
                        'instalacion' => 'Instalación',
                        'desinstalacion' => 'Desinstalación',
                        'soporte' => 'Cambio de bateria',
                        'diagnostico' => 'Visita técnica de diagnóstico',
                        'mantenimiento' => 'Visita técnica de mantenimiento',
                        'reparacion' => 'Reparación',
                        'presupuesto' => 'Presupuestar',
                    ]),
                    

                Filter::make('fecha')
                    ->label('Rango de fechas')
                    ->form([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('due_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('due_date', '<=', $data['until']));
                    }),

                Filter::make('vencidos')
                    ->label('Vencidos')
                    ->query(fn ($query) =>
                        $query
                            ->where('status', '!=', 'completed')
                            ->where('due_date', '<', now())
                    ),

                Filter::make('sin_asignar')
                    ->label('Sin técnico')
                    ->query(fn ($query) =>
                        $query->whereNull('user_id')
                    ),

                SelectFilter::make('mine')
                    ->label('Mis pendientes')
                    ->options([
                        '1' => 'Solo míos',
                    ])
                    ->query(function ($query, $data) {
                        if ($data['value'] ?? false) {
                            $query->where('user_id', auth()->id());
                        }
                    }),
            ])

            ->actions([
                Action::make('en_proceso')
                    ->label('Tomar')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->status = 'in_progress';
                        $record->user_id = auth()->id();
                        $record->save();
                    }),

                Action::make('finalizar')
                    ->label('Cerrar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'completed')
                    ->action(function ($record) {
                        $record->status = 'completed';
                        $record->completed_at = now();
                        $record->save();
                    }),

                Action::make('asignarme')
                    ->label('Asignarme')
                    ->color('info')
                    ->visible(fn ($record) => !$record->user_id)
                    ->action(function ($record) {
                        $record->user_id = auth()->id();
                        $record->save();
                    }),

                EditAction::make(),
            ])

                ->recordClasses(fn ($record) =>
        $record->status !== 'completed' && $record->due_date && $record->due_date < now()
            ? 'bg-red-50 border-l-4 border-red-500'
            : ($record->priority === 'alta'
                ? 'bg-yellow-50 border-l-4 border-yellow-400'
                : null)
    )

            ->defaultSort('due_date', 'asc');
    }
}