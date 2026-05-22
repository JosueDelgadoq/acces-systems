<?php

namespace App\Filament\Resources\ProductoVariantes\Schemas;

use App\Models\ProductoVariante;
use App\Models\Repuesto;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductoVarianteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Variante')
                    ->schema([
                        Select::make('producto_id')
                            ->label('Producto')
                            ->relationship('producto', 'nombre')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('variant_code')
                            ->label('Código de variante')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('codigo_barra')
                            ->label('Código legacy / escaneo')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('subtype')
                            ->label('Subtipo')
                            ->maxLength(255),
                        TextInput::make('modelo')
                            ->label('Modelo')
                            ->maxLength(255),
                        Select::make('lado')
                            ->label('Lado')
                            ->options(ProductoVariante::sideOptions())
                            ->native(false),
                        Select::make('uso')
                            ->label('Uso')
                            ->options(ProductoVariante::usageOptions())
                            ->native(false),
                        Select::make('estado')
                            ->label('Estado de variante')
                            ->options([
                                'nuevo' => 'Nuevo',
                                'usado' => 'Usado',
                            ])
                            ->native(false),
                    ])
                    ->columns(3),
                Section::make('Atributos')
                    ->schema([
                        KeyValue::make('attributes_json')
                            ->label('Atributos adicionales')
                            ->keyLabel('Campo')
                            ->valueLabel('Valor'),
                    ]),
                Repeater::make('repuestos')
                    ->relationship()
                    ->label('Componentes del equipo')
                    ->schema([
                        Select::make('repuesto_id')
                            ->label('Repuesto')
                            ->options(fn () => Repuesto::pluck('nombre', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('cantidad')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        Toggle::make('obligatorio')
                            ->default(true),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->cloneable(),
            ]);
    }
}
