<?php

namespace App\Filament\Resources\Claims\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ClaimForm
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
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('Estado')
                    ->required()
                    ->default('nuevo')
                    ->options([
                        'nuevo' => 'Nuevo',
                        'en_proceso' => 'En proceso',
                        'pendiente' => 'Pendiente',
                        'resuelto' => 'Resuelto',
                        'cerrado' => 'Cerrado',
                    ]),
                Select::make('technician_id')
                    ->label('Técnico')
                    ->relationship('technician', 'name')
                    ->searchable()
                    ->default(null),
                DatePicker::make('scheduled_visit')
                    ->label('Visita programada')
                    ->placeholder('Seleccionar fecha'),
                Textarea::make('resolution')
                    ->label('Resolución')
                    ->default(null)
                    ->columnSpanFull(),
                DateTimePicker::make('closed_at')
                    ->label('Fecha de cierre')
                    ->placeholder('Seleccionar fecha'),
            ]);
    }
}
