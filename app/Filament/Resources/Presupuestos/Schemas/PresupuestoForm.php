<?php

namespace App\Filament\Resources\Presupuestos\Schemas;

use App\Models\Lead;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PresupuestoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('lead_id')
                    ->label('Lead')
                    ->relationship('lead', 'id')
                    ->getOptionLabelFromRecordUsing(fn (Lead $record) => "{$record->crm_id} - {$record->cliente}")
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('producto_1')
                    ->label('Producto 1')
                    ->maxLength(255),
                TextInput::make('precio_1')
                    ->label('Precio 1')
                    ->numeric(),

                TextInput::make('producto_2')
                    ->label('Producto 2')
                    ->maxLength(255),
                TextInput::make('precio_2')
                    ->label('Precio 2')
                    ->numeric(),

                TextInput::make('producto_3')
                    ->label('Producto 3')
                    ->maxLength(255),
                TextInput::make('precio_3')
                    ->label('Precio 3')
                    ->numeric(),

                TextInput::make('producto_4')
                    ->label('Producto 4')
                    ->maxLength(255),
                TextInput::make('precio_4')
                    ->label('Precio 4')
                    ->numeric(),

                TextInput::make('presupuesto_definitivo')
                    ->label('Presupuesto definitivo')
                    ->numeric(),

                DatePicker::make('fecha_envio')
                    ->label('Fecha de envío')
                    ->required(),

                Hidden::make('created_by')
                    ->default(fn () => auth()->id()),
            ]);
    }
}
