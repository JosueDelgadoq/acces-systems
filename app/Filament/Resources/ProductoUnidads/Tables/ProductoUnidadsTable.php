<?php

namespace App\Filament\Resources\ProductoUnidads\Tables;

use App\Models\ProductoUnidad;
use App\Services\ProductoUnidadInventoryService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductoUnidadsTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('inventory_code')
                    ->label('Código interno')
                    ->searchable(['inventory_code', 'codigo_barra', 'serial_number'])
                    ->copyable(),
                TextColumn::make('serial_number')
                    ->label('Serie')
                    ->placeholder('Sin serie')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('variante.producto.nombre')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('variante.inventory_label')
                    ->label('Variante')
                    ->toggleable()
                    ->placeholder('Sin variante'),
                TextColumn::make('estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ProductoUnidad::STATUS_OPTIONS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        ProductoUnidad::STATUS_AVAILABLE => 'success',
                        ProductoUnidad::STATUS_RESERVED => 'warning',
                        ProductoUnidad::STATUS_ASSIGNED => 'info',
                        ProductoUnidad::STATUS_INSTALLED => 'primary',
                        ProductoUnidad::STATUS_SOLD => 'danger',
                        ProductoUnidad::STATUS_REPAIR => 'gray',
                        ProductoUnidad::STATUS_RETIRED => 'danger',
                        default => 'secondary',
                    }),
                TextColumn::make('condition_label')
                    ->label('Condición')
                    ->badge()
                    ->placeholder('Sin condición'),
                TextColumn::make('location.name')
                    ->label('Ubicación')
                    ->placeholder('Sin ubicación')
                    ->toggleable(),
                TextColumn::make('client.company')
                    ->label('Cliente')
                    ->placeholder('Sin asignar')
                    ->toggleable(),
                TextColumn::make('movements_count')
                    ->counts('movements')
                    ->label('Mov.')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('updated_at')
                    ->label('Último cambio')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('producto_variante_id')
                    ->relationship('variante', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->inventory_label)
                    ->label('Variante'),
                SelectFilter::make('estado')
                    ->options(ProductoUnidad::STATUS_OPTIONS),
                SelectFilter::make('condition')
                    ->options(ProductoUnidad::CONDITION_OPTIONS),
                SelectFilter::make('location_id')
                    ->relationship('location', 'name')
                    ->label('Ubicación'),
            ])
            ->actions([
                Action::make('estado')
                    ->label('Cambiar estado')
                    ->icon('heroicon-o-pencil')
                    ->fillForm(fn (ProductoUnidad $record): array => [
                        'estado' => $record->estado,
                    ])
                    ->form([
                        Forms\Components\Select::make('estado')
                            ->label('Estado destino')
                            ->options(ProductoUnidad::STATUS_OPTIONS)
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('motivo')
                            ->label('Motivo')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(3),
                    ])
                    ->action(function (ProductoUnidad $record, array $data): void {
                        app(ProductoUnidadInventoryService::class)->changeState(
                            unit: $record,
                            targetState: (string) $data['estado'],
                            actor: auth()->user(),
                            reason: $data['motivo'] ?? null,
                            notes: $data['observaciones'] ?? null,
                        );
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
