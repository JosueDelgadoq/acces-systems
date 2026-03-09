<?php

namespace App\Filament\Resources\Conservations;

use App\Filament\Resources\Conservations\Pages\CreateConservation;
use App\Filament\Resources\Conservations\Pages\EditConservation;
use App\Filament\Resources\Conservations\Pages\ListConservations;
use App\Filament\Resources\Conservations\Schemas\ConservationForm;
use App\Filament\Resources\Conservations\Tables\ConservationsTable;
use App\Models\Conservation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ConservationResource extends Resource
{
    protected static ?string $model = Conservation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'client.name';
    public static function form(Schema $schema): Schema
    {
        return ConservationForm::form($schema);
    }

    public static function table(Table $table): Table
    {
        return ConservationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConservations::route('/'),
            'create' => CreateConservation::route('/create'),
            'edit' => EditConservation::route('/{record}/edit'),
        ];
    }
}