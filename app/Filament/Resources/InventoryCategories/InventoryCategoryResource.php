<?php

namespace App\Filament\Resources\InventoryCategories;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\InventoryCategories\Pages\CreateInventoryCategory;
use App\Filament\Resources\InventoryCategories\Pages\EditInventoryCategory;
use App\Filament\Resources\InventoryCategories\Pages\ListInventoryCategories;
use App\Models\InventoryCategory;
use App\Support\Modules\InventarioPermissions;
use BackedEnum;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class InventoryCategoryResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = InventoryCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Categorías';

    protected static string|UnitEnum|null $navigationGroup = 'Stock';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationPermission = InventarioPermissions::VIEW;

    protected static ?string $viewAnyPermission = InventarioPermissions::VIEW;

    protected static ?string $createPermission = InventarioPermissions::CREATE;

    protected static ?string $updatePermission = InventarioPermissions::UPDATE;

    protected static ?string $deletePermission = InventarioPermissions::DELETE;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Categoría')
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('code')
                        ->label('Código')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('sort_order')
                        ->label('Orden')
                        ->numeric()
                        ->default(0),
                    Toggle::make('is_active')
                        ->label('Activa')
                        ->default(true),
                    Textarea::make('description')
                        ->label('Descripción')
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
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('code')
                    ->label('Código')
                    ->placeholder('Sin código'),
                TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->defaultSort('sort_order');
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
            'index' => ListInventoryCategories::route('/'),
            'create' => CreateInventoryCategory::route('/create'),
            'edit' => EditInventoryCategory::route('/{record}/edit'),
        ];
    }
}
