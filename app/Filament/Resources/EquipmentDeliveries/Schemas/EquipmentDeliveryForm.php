<?php

namespace App\Filament\Resources\EquipmentDeliveries\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EquipmentDeliveryForm
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
                TextInput::make('equipment')
                    ->required()
                    ->label('Equipo'),
                Select::make('status')
                    ->required()
                    ->label('Estado')
                    ->options([
                        'pendiente_confirmacion' => 'Pendiente Confirmación',
                        'confirmado' => 'Confirmado',
                        'flete_solicitado' => 'Flete Solicitado',
                        'en_camino' => 'En Camino',
                        'entregado' => 'Entregado',
                        'instalado' => 'Instalado',
                    ])
                    ->default('pendiente_confirmacion'),
                DatePicker::make('sale_date')
                    ->label('Fecha de Venta'),
                DatePicker::make('delivery_date')
                    ->label('Fecha de Entrega'),
                DatePicker::make('installation_date')
                    ->label('Fecha de Instalación'),
                TextInput::make('delivery_remito')
                    ->label('Remito de Entrega'),
                TextInput::make('installation_remito')
                    ->label('Remito de Instalación'),
                Select::make('signed_remito')
                    ->label('Remito Firmado')
                    ->options([
                        '0' => 'No',
                        '1' => 'Sí',
                    ])
                    ->default('0'),
                Textarea::make('observations')
                    ->label('Observaciones')
                    ->columnSpanFull(),
            ]);
    }
}

