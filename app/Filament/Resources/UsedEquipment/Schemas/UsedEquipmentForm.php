<?php

namespace App\Filament\Resources\UsedEquipment\Schemas;

use App\Models\UsedEquipment;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UsedEquipmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Identificacion')
                ->schema([
                    TextInput::make('code')
                        ->label('Codigo')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Se genera automaticamente')
                        ->helperText('Para sillas nuevas se usara el formato SU-MA-0001 segun marca.'),
                    Select::make('type')
                        ->label('Tipo')
                        ->options(UsedEquipment::TYPE_OPTIONS)
                        ->required()
                        ->live(),
                    Select::make('brand')
                        ->label('Marca')
                        ->options(UsedEquipment::BRAND_OPTIONS)
                        ->searchable()
                        ->live(),
                    Select::make('status')
                        ->label('Estado')
                        ->options(UsedEquipment::STATUS_OPTIONS)
                        ->default('a_revisar')
                        ->required(),
                    Select::make('ingress_condition')
                        ->label('Condicion de ingreso')
                        ->options(UsedEquipment::INGRESS_CONDITION_OPTIONS),
                ])
                ->columns(2),
            Section::make('Deposito')
                ->schema([
                    Select::make('warehouse_zone')
                        ->label('Zona de deposito')
                        ->options(UsedEquipment::WAREHOUSE_ZONE_OPTIONS)
                        ->helperText('Verde = venta, Amarilla = recuperacion, Roja = repuestos.'),
                    TextInput::make('location')
                        ->label('Ubicacion fisica')
                        ->placeholder('Ej: Estante A2 / Rack 3 / Pasillo 1'),
                ])
                ->columns(2),
            Section::make('Estado tecnico')
                ->schema([
                    CheckboxList::make('missing_parts')
                        ->label('Faltantes')
                        ->options(UsedEquipment::MISSING_PART_OPTIONS)
                        ->columns(2)
                        ->dehydrated(true),
                    Textarea::make('notes')
                        ->label('Observaciones')
                        ->rows(4),
                ]),
            Section::make('Fotos')
                ->schema([
                    FileUpload::make('photos')
                        ->label('Imagenes del equipo')
                        ->image()
                        ->multiple()
                        ->disk('public')
                        ->directory('used-equipment')
                        ->maxFiles(8)
                        ->maxSize(51200)
                        ->imagePreviewHeight('140')
                        ->helperText('Opcional. Hasta 8 imagenes de 50 MB cada una para ver estado real, faltantes y trazabilidad visual.'),
                ]),
            Section::make('QR y ficha')
                ->schema([
                    TextInput::make('public_url')
                        ->label('URL de ficha')
                        ->formatStateUsing(fn (?UsedEquipment $record): ?string => $record?->public_url)
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (?UsedEquipment $record): bool => filled($record)),
                    TextInput::make('label_url')
                        ->label('URL de etiqueta')
                        ->formatStateUsing(fn (?UsedEquipment $record): ?string => $record?->label_url)
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (?UsedEquipment $record): bool => filled($record)),
                ])
                ->columns(2)
                ->visible(fn (?UsedEquipment $record): bool => filled($record)),
        ]);
    }
}
