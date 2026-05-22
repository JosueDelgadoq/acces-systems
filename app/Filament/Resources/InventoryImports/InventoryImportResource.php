<?php

namespace App\Filament\Resources\InventoryImports;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\InventoryImports\Pages\CreateInventoryImport;
use App\Filament\Resources\InventoryImports\Pages\EditInventoryImport;
use App\Filament\Resources\InventoryImports\Pages\ListInventoryImports;
use App\Filament\Resources\InventoryImports\RelationManagers\RowsRelationManager;
use App\Models\InventoryImportBatch;
use App\Services\InventoryImportService;
use App\Support\Modules\InventarioPermissions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class InventoryImportResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = InventoryImportBatch::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static ?string $navigationLabel = 'Importaciones';

    protected static string|UnitEnum|null $navigationGroup = 'Stock';

    protected static ?int $navigationSort = 12;

    protected static ?string $navigationPermission = InventarioPermissions::VIEW;

    protected static ?string $viewAnyPermission = InventarioPermissions::VIEW;

    protected static ?string $createPermission = InventarioPermissions::CREATE;

    protected static ?string $updatePermission = InventarioPermissions::UPDATE;

    protected static ?string $deletePermission = InventarioPermissions::DELETE;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Archivo')
                ->schema([
                    FileUpload::make('file_path')
                        ->label('Excel de stock')
                        ->disk('local')
                        ->directory('inventory-imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'application/vnd.oasis.opendocument.spreadsheet',
                        ])
                        ->required(),
                    Hidden::make('file_disk')
                        ->default('local'),
                    Textarea::make('notes')
                        ->label('Notas')
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Estado')
                ->schema([
                    TextInput::make('file_name')
                        ->label('Archivo')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (?InventoryImportBatch $record): ?string => $record?->file_name),
                    TextInput::make('status_label')
                        ->label('Estado')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (?InventoryImportBatch $record): ?string => $record?->status_label),
                    Textarea::make('summary_preview')
                        ->label('Resumen')
                        ->rows(10)
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (?InventoryImportBatch $record): ?string => $record && filled($record->summary)
                            ? json_encode($record->summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                            : null)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->visible(fn (?InventoryImportBatch $record): bool => filled($record)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_name')
                    ->label('Archivo')
                    ->searchable(),
                TextColumn::make('status_label')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('summary.total_rows')
                    ->label('Filas')
                    ->placeholder('0'),
                TextColumn::make('summary.imported_rows')
                    ->label('Importadas')
                    ->placeholder('0'),
                TextColumn::make('summary.error_rows')
                    ->label('Errores')
                    ->placeholder('0'),
                TextColumn::make('summary.conflict_rows')
                    ->label('Conflictos')
                    ->placeholder('0'),
                TextColumn::make('previewed_at')
                    ->label('Preview')
                    ->dateTime()
                    ->placeholder('Pendiente'),
                TextColumn::make('imported_at')
                    ->label('Importación')
                    ->dateTime()
                    ->placeholder('Pendiente'),
                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->requiresConfirmation()
                    ->action(function (InventoryImportBatch $record): void {
                        app(InventoryImportService::class)->preview($record, auth()->user());

                        Notification::make()
                            ->title('Preview generada')
                            ->success()
                            ->send();
                    }),
                Action::make('import')
                    ->label('Importar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (InventoryImportBatch $record): void {
                        app(InventoryImportService::class)->import($record, auth()->user());

                        Notification::make()
                            ->title('Importación completada')
                            ->success()
                            ->send();
                    }),
                Action::make('rollback')
                    ->label('Rollback')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (InventoryImportBatch $record): bool => filled($record->imported_at))
                    ->action(function (InventoryImportBatch $record): void {
                        app(InventoryImportService::class)->rollback($record, auth()->user());

                        Notification::make()
                            ->title('Rollback aplicado')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
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
        return [
            RowsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryImports::route('/'),
            'create' => CreateInventoryImport::route('/create'),
            'edit' => EditInventoryImport::route('/{record}/edit'),
        ];
    }
}
