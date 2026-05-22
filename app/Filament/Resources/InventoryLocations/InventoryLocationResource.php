<?php

namespace App\Filament\Resources\InventoryLocations;

use App\Enums\Inventory\InventoryLocationType;
use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\InventoryLocations\Pages\CreateInventoryLocation;
use App\Filament\Resources\InventoryLocations\Pages\EditInventoryLocation;
use App\Filament\Resources\InventoryLocations\Pages\ListInventoryLocations;
use App\Models\InventoryLocation;
use App\Support\Modules\InventarioPermissions;
use BackedEnum;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class InventoryLocationResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = InventoryLocation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Ubicaciones';

    protected static string|UnitEnum|null $navigationGroup = 'Stock';

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationPermission = InventarioPermissions::VIEW;

    protected static ?string $viewAnyPermission = InventarioPermissions::VIEW;

    protected static ?string $createPermission = InventarioPermissions::CREATE;

    protected static ?string $updatePermission = InventarioPermissions::UPDATE;

    protected static ?string $deletePermission = InventarioPermissions::DELETE;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ubicación')
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('code')
                        ->label('Código')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Select::make('type')
                        ->label('Tipo')
                        ->options(InventoryLocationType::options())
                        ->required()
                        ->native(false),
                    Select::make('parent_id')
                        ->label('Ubicación padre')
                        ->relationship('parent', 'name')
                        ->searchable()
                        ->preload(),
                    Toggle::make('is_active')
                        ->label('Activa')
                        ->default(true),
                    Textarea::make('notes')
                        ->label('Notas')
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable(),
                TextColumn::make('type_label')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('parent.name')
                    ->label('Padre')
                    ->placeholder('Raíz'),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->defaultSort('name');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return InventarioPermissions::view() || InventarioPermissions::viewAll();
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryLocations::route('/'),
            'create' => CreateInventoryLocation::route('/create'),
            'edit' => EditInventoryLocation::route('/{record}/edit'),
        ];
    }
}
