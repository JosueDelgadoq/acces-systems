<?php

namespace App\Filament\Resources\BillingControls\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BillingControlForm
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
                TextInput::make('service_description')
                    ->required()
                    ->label('Descripción del Servicio'),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->label('Monto')
                    ->prefix('$'),
                TextInput::make('invoice_number')
                    ->label('Número de Factura'),
                DatePicker::make('invoice_date')
                    ->label('Fecha de Factura'),
                Select::make('invoiced')
                    ->label('¿Se Facturó?')
                    ->options([
                        '0' => 'No',
                        '1' => 'Sí',
                    ])
                    ->default('0'),
                Select::make('paid')
                    ->label('¿Se Cobró?')
                    ->options([
                        '0' => 'No',
                        '1' => 'Sí',
                    ])
                    ->default('0'),
                DatePicker::make('payment_date')
                    ->label('Fecha de Cobro'),
                Textarea::make('observations')
                    ->label('Observaciones')
                    ->columnSpanFull(),
            ]);
    }
}

