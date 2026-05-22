<?php

namespace App\Filament\Resources\Ventas\Schemas;

use App\Models\Lead;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VentaForm
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

                TextInput::make('producto_instalado')
                    ->label('Producto instalado')
                    ->required()
                    ->maxLength(255),

                TextInput::make('monto_total')
                    ->label('Monto total')
                    ->required()
                    ->numeric(),

                Select::make('estado')
                    ->options([
                        'Cerrada' => 'Cerrada',
                        'Cancelada' => 'Cancelada',
                    ])
                    ->required(),

                DatePicker::make('fecha_cierre')
                    ->label('Fecha de cierre')
                    ->required(),

                Hidden::make('created_by')
                    ->default(fn () => auth()->id()),
            ]);
    }
}
