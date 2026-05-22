<?php

namespace App\Filament\Resources\Repuestos\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Forms;
use App\Services\StockRepuestoService;


class RepuestosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

Tables\Columns\TextColumn::make('tipo')
    ->label('Tipo')
    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('codigo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('stock_actual')
                    ->label('Stock')
                    ->badge()
                    ->sortable()
                    ->color(fn ($record) =>
                        $record->stock_actual <= $record->stock_minimo
                            ? 'danger'
                            : 'success'
                    ),

                Tables\Columns\TextColumn::make('stock_minimo')
                    ->label('Mínimo')
                    ->sortable(),

            ])

->filters([
    Tables\Filters\SelectFilter::make('tipo')
        ->options([
            'cargador' => 'Cargador',
            'pilas' => 'Pilas',
            'bateria' => 'Batería',
            'control' => 'Control',
            'accesorio' => 'Accesorio',
            'ruleman' => 'Rulemán',
        ]),
])

            ->recordActions([

                // ➕ ENTRADA
                Action::make('entrada')
                    ->label('➕')
                    ->color('success')
                    ->modalHeading('Agregar stock')
                    ->form([
                        Forms\Components\TextInput::make('cantidad')
                            ->numeric()
                            ->required(),

                        Forms\Components\Textarea::make('motivo'),
                    ])
                    ->action(function ($record, $data) {
                        StockRepuestoService::entrada(
                            $record->id,
                            $data['cantidad'],
                            $data['motivo']
                        );
                    }),

                // ➖ SALIDA
                Action::make('salida')
                    ->label('➖')
                    ->color('danger')
                    ->modalHeading('Quitar stock')
                    ->form([
                        Forms\Components\TextInput::make('cantidad')
                            ->numeric()
                            ->required(),

                        Forms\Components\Textarea::make('motivo'),
                    ])
                    ->action(function ($record, $data) {
                        StockRepuestoService::salida(
                            $record->id,
                            $data['cantidad'],
                            $data['motivo']
                        );
                    }),

                EditAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}