<?php

namespace App\Filament\Resources\Productos\Schemas;

use App\Enums\Inventory\InventoryTrackingMode;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Catálogo')
                    ->schema([
                        TextInput::make('nombre')
                            ->label('Nombre del producto')
                            ->required()
                            ->maxLength(255),
                        Select::make('category_id')
                            ->label('Categoría')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('tipo')
                            ->label('Tipo legacy')
                            ->options([
                                'silla' => 'Silla',
                                'motor' => 'Motor',
                                'plataforma' => 'Plataforma',
                                'guia' => 'Guía',
                                'oruga' => 'Oruga',
                                'aleron' => 'Alerón',
                                'componente' => 'Componente',
                                'repuesto' => 'Repuesto',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),
                Section::make('Trazabilidad')
                    ->schema([
                        TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('brand')
                            ->label('Marca')
                            ->maxLength(255),
                        Select::make('tracking_mode')
                            ->label('Modo de seguimiento')
                            ->options(InventoryTrackingMode::options())
                            ->default(InventoryTrackingMode::UNITIZED->value)
                            ->required()
                            ->native(false),
                    ])
                    ->columns(3),
            ]);
    }
}
