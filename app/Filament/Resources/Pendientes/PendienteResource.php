<?php

namespace App\Filament\Resources\Pendientes;

use App\Filament\Resources\Pendientes\Pages;
use App\Filament\Resources\Pendientes\Schemas\PendienteForm;
use App\Filament\Resources\Pendientes\Tables\PendienteTable;
use App\Models\Pendiente;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;

class PendienteResource extends Resource
{
    protected static ?string $model = Pendiente::class;
    public static function getNavigationBadge(): ?string
{
return Pendiente::whereIn('status', ['pending', 'in_progress'])->count();}
public static function getNavigationBadgeColor(): ?string
{
    $vencidos = Pendiente::where('status', '!=', 'completed')
        ->where('due_date', '<', now())
        ->count();

    return $vencidos > 0 ? 'danger' : 'warning';
    
}

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Pendientes';
    protected static ?string $modelLabel = 'Pendiente';
    protected static ?string $pluralModelLabel = 'Pendientes';
    protected static ?int $navigationSort = 2;
    protected static UnitEnum|string|null $navigationGroup = 'Gestión';

public static function form(Schema $schema): Schema
{
    return PendienteForm::configure($schema);
}

    public static function table(Table $table): Table
    {
        return PendienteTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

public static function getPages(): array
{
    return [
        'index' => Pages\ListPendientes::route('/'),
        'create' => Pages\CreatePendiente::route('/create'),
        'edit' => Pages\EditPendiente::route('/{record}/edit'),
    ];
}
}