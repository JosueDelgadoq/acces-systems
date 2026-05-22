<?php

namespace App\Filament\Resources\GuiaItems\Schemas;

use App\Models\ProductoVariante;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GuiaItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('producto_variante_id')
                    ->label('Variante')
                    ->relationship(
                        name: 'productoVariante',
                        titleAttribute: 'id',
                        modifyQueryUsing: fn ($query) => $query
                            ->with('producto')
                            ->whereHas('producto', fn ($productQuery) => $productQuery->where('tipo', 'guia'))
                    )
                    ->getOptionLabelFromRecordUsing(fn (ProductoVariante $record) => $record->display_name)
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('longitud')
                    ->label('Longitud (m)')
                    ->numeric()
                    ->step(0.01)
                    ->required(),

                Toggle::make('tiene_angulo')
                    ->label('Ángulo'),

                Toggle::make('tiene_patas')
                    ->label('Patas'),

                Toggle::make('rebatible')
                    ->label('Rebatible'),

                Select::make('estado')
                    ->label('Estado')
                    ->options([
                        'nuevo' => 'Nuevo',
                        'usado' => 'Usado',
                    ])
                    ->default('usado'),

                Textarea::make('observaciones')
                    ->rows(3),
            ]);
    }
}
