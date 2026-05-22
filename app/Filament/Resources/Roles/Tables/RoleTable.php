<?php

namespace App\Filament\Resources\Roles\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoleTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Rol')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permisos')
                    ->badge()
                    ->color('info'),
                TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Usuarios')
                    ->badge()
                    ->color('success'),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make()->label('Editar'),
                DeleteAction::make()
                    ->visible(fn ($record): bool => $record->name !== 'admin'),
            ]);
    }
}
