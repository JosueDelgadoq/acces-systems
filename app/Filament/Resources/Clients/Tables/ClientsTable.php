<?php

namespace App\Filament\Resources\Clients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
        ->modifyQueryUsing(function ($query) {
        return $query->select('clients.*')->distinct();
    })
            ->columns([
                TextColumn::make('id')
                    ->label('ID ERP')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                TextColumn::make('cuil')
                    ->label('CUIL/CUIT')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    TextColumn::make('equipos')
                        ->label('Equipos')
                        ->badge()
                        ->formatStateUsing(function ($record) {
                            return $record->equipos()
                                ->with('equipo')
                                ->get()
                                ->map(fn ($e) => $e->equipo->nombre)
                                ->unique()
                                ->values()
                                ->join(', ');
                        })
                        ->color('info'),
                TextColumn::make('installation_date')
                    ->label('Instalación')
                    ->date('d/m/Y')
                    ->searchable()
                    ->sortable(),  
                TextColumn::make('id_crm')
                    ->label('ID CRM')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
