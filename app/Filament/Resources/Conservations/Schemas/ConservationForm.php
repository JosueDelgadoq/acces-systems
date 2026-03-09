<?php

namespace App\Filament\Resources\Conservations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ConservationForm
{
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('client_id')
                    ->label('Cliente ID')
                    ->required()
                    ->numeric(),

                DatePicker::make('start_date')
                    ->label('Inicio del contrato')
                    ->required(),

                DatePicker::make('next_service_date')
                    ->label('Próximo servicio')
                    ->required(),

                DatePicker::make('expiration_date')
                    ->label('Vencimiento')
                    ->required(),

                Select::make('frequency')
                    ->label('Frecuencia de mantenimiento')
                    ->options([
                        'mensual' => 'Mensual',
                        'trimestral' => 'Trimestral',
                        'semestral' => 'Semestral',
                        'anual' => 'Anual',
                    ])
                    ->required(),

                Textarea::make('notes')
                    ->label('Notas')
                    ->columnSpanFull(),

            ]);
    }
}