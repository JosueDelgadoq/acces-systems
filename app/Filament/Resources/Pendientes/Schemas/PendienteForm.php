<?php

namespace App\Filament\Resources\Pendientes\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;

class PendienteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('client_id')
                ->label('Cliente')
                ->relationship('client', 'name')
                ->searchable()
                ->required(),

            Select::make('user_id')
                ->label('Asignado a')
                ->relationship('user', 'name')
                ->searchable()
                ->nullable(),

            Select::make('type')
                ->label('Tipo')
                ->options([
                    'call' => 'Llamada',
                    'email' => 'Email',
                    'meeting' => 'Reunión',
                    'other' => 'Otro',
                ])
                ->required(),

            Textarea::make('description')
                ->label('Descripción')
                ->required()
                ->columnSpanFull(),

            DatePicker::make('due_date')
                ->label('Fecha límite')
                ->required(),

            Select::make('status')
                ->label('Estado')
                ->options([
                    'pending' => 'Pendiente',
                    'completed' => 'Completado',
                    'cancelled' => 'Cancelado',
                ])
                ->default('pending')
                ->required(),

            Textarea::make('notes')
                ->label('Notas')
                ->columnSpanFull(),
        ]);
    }
}