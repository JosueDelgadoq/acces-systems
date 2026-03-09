<?php

namespace App\Filament\Resources\PartsOrders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PartsOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('technician_id')
                    ->relationship('technician', 'name')
                    ->label('Técnico Solicitante')
                    ->searchable()
                    ->preload(),
                TextInput::make('part_name')
                    ->required()
                    ->label('Nombre del Repuesto'),
                TextInput::make('supplier')
                    ->label('Proveedor'),
                TextInput::make('estimated_cost')
                    ->numeric()
                    ->label('Costo Estimado')
                    ->prefix('$'),
                Select::make('status')
                    ->required()
                    ->label('Estado')
                    ->options([
                        'pendiente_cotizacion' => 'Pendiente Cotización',
                        'cotizado' => 'Cotizado',
                        'aprobado' => 'Aprobado',
                        'comprado' => 'Comprado',
                        'recibido' => 'Recibido',
                        'entregado_tecnico' => 'Entregado al Técnico',
                    ])
                    ->default('pendiente_cotizacion'),
                DatePicker::make('purchase_date')
                    ->label('Fecha de Compra'),
                DatePicker::make('arrival_date')
                    ->label('Fecha de Llegada Estimada'),
                TextInput::make('invoice')
                    ->label('Factura del Proveedor'),
                Textarea::make('observations')
                    ->label('Observaciones')
                    ->columnSpanFull(),
            ]);
    }
}

