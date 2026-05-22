<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Resources\Clients\RelationManagers\ClaimsRelationManager;
use App\Filament\Resources\Clients\Schemas\ClientForm;
use App\Filament\Resources\Clients\Tables\ClientsTable;
use App\Models\Client;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ClientResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = Client::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | UnitEnum | null $navigationGroup = 'Gestion';

    protected static ?string $navigationPermission = 'cliente.view';

    protected static ?string $viewAnyPermission = 'cliente.view';

    protected static ?string $createPermission = 'cliente.create';

    protected static ?string $updatePermission = 'cliente.update';

    protected static ?string $deletePermission = 'cliente.delete';

    public static function form(Schema $schema): Schema
    {
        return ClientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ClaimsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
    }
}
