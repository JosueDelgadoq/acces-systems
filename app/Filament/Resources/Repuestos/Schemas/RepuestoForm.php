<?php

namespace App\Filament\Resources\Repuestos\Schemas;

use Filament\Forms;

class RepuestoForm
{
    public static function schema(): array
    {
        return [
            Forms\Components\Select::make('tipo')
    ->options([
        'cargador' => 'Cargador',
        'pilas' => 'Pilas',
        'bateria' => 'Batería',
        'control' => 'Control',
        'accesorio' => 'Accesorio',
        'ruleman' => 'Rulemán',
    ])
    ->required(),

Forms\Components\TextInput::make('nombre')
    ->required()
    ->placeholder('Ej: 36v, AA, AS30, 6204-2RS'),

Forms\Components\TextInput::make('codigo')
    ->label('Código'),

Forms\Components\Textarea::make('descripcion'),

Forms\Components\TextInput::make('stock_actual')
    ->numeric()
    ->disabled(),

Forms\Components\TextInput::make('stock_minimo')
    ->numeric()
    ->default(0),

Forms\Components\TextInput::make('precio_venta')
    ->numeric(),
        ];
    }
}