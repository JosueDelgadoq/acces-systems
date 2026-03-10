<?php

namespace App\Filament\Resources\Tickets\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('title')
                    ->label('Título')
                    ->required(),
                Textarea::make('description')
                    ->label('Descripción')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('Estado')
                    ->required()
                    ->default('open')
                    ->options([
                        'open' => 'Abierto',
                        'in_progress' => 'En progreso',
                        'closed' => 'Cerrado',
                        'pending' => 'Pendiente',
                    ]),
                Select::make('priority')
                    ->label('Prioridad')
                    ->required()
                    ->default('normal')
                    ->options([
                        'low' => 'Baja',
                        'normal' => 'Normal',
                        'high' => 'Alta',
                        'urgent' => 'Urgente',
                    ]),
            ]);
    }
}
