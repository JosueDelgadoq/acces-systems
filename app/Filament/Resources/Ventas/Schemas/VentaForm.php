<?php

namespace App\Filament\Resources\Ventas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VentaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('cliente_nombre')
                    ->required(),
                TextInput::make('producto')
                    ->required(),
                TextInput::make('monto')
                    ->required()
                    ->numeric(),
                TextInput::make('estado')
                    ->required(),
                DatePicker::make('fecha')
                    ->required(),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
            ]);
    }
}
