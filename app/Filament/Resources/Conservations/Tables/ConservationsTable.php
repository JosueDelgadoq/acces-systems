<?php

namespace App\Filament\Resources\Conservations\Tables;

use App\Models\Conservation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

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

                TextColumn::make('contract_cycle_number')
                    ->label('Ciclo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => str_pad((string) ($state ?? 1), 2, '0', STR_PAD_LEFT))
                    ->color('gray'),

                TextColumn::make('service_progress')
                    ->label('Servicio')
                    ->badge()
                    ->color(function (Conservation $record): string {
                        if ($record->isRenewalRequired()) {
                            return 'warning';
                        }

                        if ($record->isExpired()) {
                            return 'danger';
                        }

                        return $record->remaining_services <= 2 ? 'warning' : 'success';
                    }),

                TextColumn::make('start_date')
                    ->label('Inicio contrato')
                    ->date()
                    ->sortable(),

                TextColumn::make('next_service_date')
                    ->label('Próximo servicio')
                    ->formatStateUsing(function ($state, Conservation $record): string {
                        if ($record->isRenewalRequired()) {
                            return 'Renovar contrato';
                        }

                        if (! $state) {
                            return 'Sin programar';
                        }

                        return $record->next_service_date?->format('d/m/Y') ?? 'Sin programar';
                    })
                    ->badge()
                    ->color(function (Conservation $record): string {
                        if ($record->isRenewalRequired()) {
                            return 'warning';
                        }

                        if ($record->next_service_date && $record->next_service_date->isPast()) {
                            return 'danger';
                        }

                        return 'gray';
                    }),

                TextColumn::make('expiration_date')
                    ->label('Vencimiento')
                    ->date()
                    ->sortable()
                    ->badge()
                    ->color(fn (Conservation $record) => $record->isExpired()
                        ? 'danger'
                        : ($record->expiration_date && $record->expiration_date->lt(now()->addDays(30))
                            ? 'warning'
                            : 'success')),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->getStateUsing(function (Conservation $record): string {
                        if ($record->isRenewalRequired()) {
                            return 'Renovar';
                        }

                        if ($record->isExpired()) {
                            return 'Vencido';
                        }

                        if ($record->next_service_date && $record->next_service_date->lt(now())) {
                            return 'Atrasado';
                        }

                        if ($record->next_service_date && $record->next_service_date->lt(now()->addDays(7))) {
                            return 'Próximo';
                        }

                        return 'Al día';
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Renovar' => 'warning',
                        'Vencido' => 'danger',
                        'Atrasado' => 'danger',
                        'Próximo' => 'info',
                        default => 'success',
                    }),

                TextColumn::make('frequency')
                    ->label('Frecuencia')
                    ->badge(),
            ])
            ->filters([
                Filter::make('requieren_renovacion')
                    ->label('Requieren renovación')
                    ->query(fn ($query) => $query->dueForRenewal()),

                Filter::make('vencidos')
                    ->label('Vencidos')
                    ->query(fn ($query) => $query->whereDate('expiration_date', '<', now())),

                Filter::make('por_vencer')
                    ->label('Por vencer (30 días)')
                    ->query(fn ($query) => $query->whereBetween(
                        'expiration_date',
                        [now(), now()->addDays(30)],
                    )),
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
