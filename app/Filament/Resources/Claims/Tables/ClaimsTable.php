<?php

namespace App\Filament\Resources\Claims\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClaimsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
TextColumn::make('client_id')
                    ->label('Cliente')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->searchable(),
                TextColumn::make('technician_id')
                    ->label('Técnico')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('scheduled_visit')
                    ->label('Visita programada')
                    ->date()
                    ->sortable(),
                TextColumn::make('closed_at')
                    ->label('Cerrado')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
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
