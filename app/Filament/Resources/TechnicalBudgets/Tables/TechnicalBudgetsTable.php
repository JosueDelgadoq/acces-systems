<?php

namespace App\Filament\Resources\TechnicalBudgets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TechnicalBudgetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'nuevo' => 'info',
                        'cotizado' => 'warning',
                        'enviado' => 'info',
                        'aprobado' => 'success',
                        'rechazado' => 'danger',
                        'visita_coordinada' => 'warning',
                        'cerrado' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('ARS')
                    ->sortable(),
                TextColumn::make('sent_at')
                    ->label('Fecha Envío')
                    ->date()
                    ->sortable(),
                TextColumn::make('approval_date')
                    ->label('Fecha Aprobación')
                    ->date()
                    ->sortable(),
                TextColumn::make('scheduled_visit')
                    ->label('Fecha Visita')
                    ->date()
                    ->sortable(),
                TextColumn::make('service_remito')
                    ->label('Remito')
                    ->searchable(),
                TextColumn::make('created_at')
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

