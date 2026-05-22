<?php

namespace App\Filament\Resources\ProductoUnidads\Schemas;

use App\Models\ProductoUnidad;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductoUnidadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->schema([
                        Select::make('producto_variante_id')
                            ->label('Variante')
                            ->relationship('variante', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->inventory_label)
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('inventory_code')
                            ->label('Código interno')
                            ->helperText('Si queda vacío, el sistema genera un código único.')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('codigo_barra')
                            ->label('Código legacy / escaneo')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('serial_number')
                            ->label('Número de serie')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Estado actual')
                    ->schema([
                        Select::make('estado')
                            ->label('Estado')
                            ->options(ProductoUnidad::STATUS_OPTIONS)
                            ->default(ProductoUnidad::STATUS_AVAILABLE)
                            ->required()
                            ->native(false),
                        Select::make('condition')
                            ->label('Condición')
                            ->options(ProductoUnidad::CONDITION_OPTIONS)
                            ->native(false),
                        Select::make('location_id')
                            ->label('Ubicación')
                            ->relationship('location', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),
                Section::make('Asignación e historial')
                    ->schema([
                        Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->company ? "{$record->company} | {$record->name}" : $record->name)
                            ->searchable()
                            ->preload(),
                        Select::make('client_equipo_id')
                            ->label('Equipo asociado')
                            ->relationship('clientEquipo', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->serie ? "{$record->serie} | {$record->ubicacion}" : "Instalación #{$record->id}")
                            ->searchable()
                            ->preload(),
                        DateTimePicker::make('acquired_at')
                            ->label('Fecha de ingreso'),
                        DateTimePicker::make('installed_at')
                            ->label('Fecha de instalación'),
                    ])
                    ->columns(2),
                Textarea::make('notes')
                    ->label('Observaciones')
                    ->rows(4),
            ]);
    }
}
