<?php

namespace App\Filament\Resources\TechnicalBudgets\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TechnicalBudgetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')
                    ->relationship('client', 'name')
                    ->required()
                    ->label('Cliente')
                    ->searchable()
                    ->preload(),
                TextInput::make('title')
                    ->required()
                    ->label('Título'),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                Select::make('status')
                    ->required()
                    ->label('Estado')
                    ->options([
                        'nuevo' => 'Nuevo',
                        'cotizado' => 'Cotizado',
                        'enviado' => 'Enviado',
                        'aprobado' => 'Aprobado',
                        'rechazado' => 'Rechazado',
                        'visita_coordinada' => 'Visita Coordinada',
                        'cerrado' => 'Cerrado',
                    ])
                    ->default('nuevo'),
                TextInput::make('amount')
                    ->label('Monto')
                    ->numeric()
                    ->prefix('$'),
                DatePicker::make('sent_at')
                    ->label('Fecha de Envío'),
                DatePicker::make('approval_date')
                    ->label('Fecha de Aprobación'),
                DatePicker::make('scheduled_visit')
                    ->label('Fecha de Visita Programada'),
                TextInput::make('service_remito')
                    ->label('Remito de Servicio'),
                Textarea::make('observations')
                    ->label('Observaciones')
                    ->columnSpanFull(),
            ]);
    }
}

