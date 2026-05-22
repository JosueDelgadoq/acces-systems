<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : ucfirst($state))
                    ->color(fn ($state) => match ($state) {
                        'admin' => 'danger',
                        'gerente' => 'warning',
                        'tecnico' => 'info',
                        'comercial' => 'success',
                        'administrativo' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
