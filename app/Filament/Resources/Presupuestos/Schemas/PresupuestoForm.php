<?php

namespace App\Filament\Resources\Presupuestos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PresupuestoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('lead_id')
                    ->relationship('lead', 'id')
                    ->required(),
                Textarea::make('descripcion')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('monto')
                    ->required()
                    ->numeric(),
                Select::make('estado')
                    ->options([
            'pendiente' => 'Pendiente',
            'enviado' => 'Enviado',
            'aceptado' => 'Aceptado',
            'rechazado' => 'Rechazado',
        ])
                    ->default('pendiente')
                    ->required(),
                DatePicker::make('fecha_envio')
                    ->required(),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
            ]);
    }
}
