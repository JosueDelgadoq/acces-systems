<?php

namespace App\Filament\Resources\ProductoVariantes\Tables;

use App\Models\ProductoVariante;
use App\Services\ProductoUnidadInventoryService;
use App\Support\Modules\InventarioPermissions;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductoVariantesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('variant_code')
                    ->label('Código')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('subtype')
                    ->label('Subtipo')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('modelo')
                    ->label('Modelo')
                    ->toggleable(),
                TextColumn::make('lado')
                    ->badge(),
                TextColumn::make('uso')
                    ->badge(),
                TextColumn::make('estado')
                    ->badge(),
                TextColumn::make('stock_actual')
                    ->label('Disponibles')
                    ->getStateUsing(fn (ProductoVariante $record) => $record->stock_actual)
                    ->badge()
                    ->color(fn (int $state) => $state <= 0 ? 'danger' : ($state < 5 ? 'warning' : 'success'))
                    ->sortable(false),
                TextColumn::make('unidades_totales')
                    ->label('Unidades')
                    ->getStateUsing(fn (ProductoVariante $record) => $record->unidades_totales)
                    ->badge()
                    ->color('gray')
                    ->sortable(false),
                TextColumn::make('codigo_barra')
                    ->label('Código legacy')
                    ->placeholder('Sin código')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('lado')
                    ->options(ProductoVariante::sideOptions()),
                SelectFilter::make('uso')
                    ->options(ProductoVariante::usageOptions()),
                SelectFilter::make('estado')
                    ->options([
                        'nuevo' => 'Nuevo',
                        'usado' => 'Usado',
                    ]),
                SelectFilter::make('subtype')
                    ->attribute('subtype'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('cargar_stock')
                    ->label('Cargar unidades')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->visible(fn () => InventarioPermissions::update())
                    ->form([
                        Forms\Components\TextInput::make('cantidad')
                            ->label('Cantidad')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        Forms\Components\Select::make('motivo')
                            ->label('Motivo')
                            ->options([
                                'compra' => 'Compra',
                                'devolucion' => 'Devolución',
                                'regularizacion' => 'Regularización',
                                'armado' => 'Armado',
                                'otro' => 'Otro',
                            ])
                            ->default('compra')
                            ->required()
                            ->native(false),
                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(3),
                    ])
                    ->action(function (ProductoVariante $record, array $data): void {
                        app(ProductoUnidadInventoryService::class)->createUnits(
                            variant: $record,
                            quantity: (int) $data['cantidad'],
                            actor: auth()->user(),
                            reason: (string) $data['motivo'],
                            notes: $data['observaciones'] ?? null,
                        );
                    }),
                Action::make('ver_unidades')
                    ->label('Unidades')
                    ->icon('heroicon-o-qr-code')
                    ->url(fn (ProductoVariante $record) => route('filament.admin.resources.producto-unidads.index', [
                        'tableFilters[producto_variante_id][value]' => $record->id,
                    ])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
