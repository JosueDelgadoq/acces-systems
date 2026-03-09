<?php

namespace App\Filament\Resources\Habilitations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HabilitationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('equipment')
                    ->label('Equipo')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'gray',
                        'documentacion' => 'warning',
                        'enviado_gestor' => 'info',
                        'en_tramite' => 'warning',
                        'aprobado' => 'success',
                        'rechazado' => 'danger',
                        'archivado' => 'gray',
                    }),
                TextColumn::make('doc_completa')
                    ->label('Doc. Completa')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
                TextColumn::make('fecha_envio_gestor')
                    ->label('Fecha Envío')
                    ->date()
                    ->sortable(),
                TextColumn::make('fecha_presentacion')
                    ->label('Fecha Presentación')
                    ->date()
                    ->sortable(),
                TextColumn::make('proxima_gestion')
                    ->label('Próxima Gestión')
                    ->date()
                    ->sortable(),
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

