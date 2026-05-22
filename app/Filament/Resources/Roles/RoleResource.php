<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\Roles\Pages;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Filament\Resources\Roles\Tables\RoleTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;
use UnitEnum;

class RoleResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = Role::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Roles';

    protected static ?string $modelLabel = 'Rol';

    protected static ?string $pluralModelLabel = 'Roles';

    protected static ?int $navigationSort = 1;

    protected static UnitEnum | string | null $navigationGroup = 'BLOQUE A - Admin';

    protected static ?string $navigationPermission = 'roles.manage';

    protected static ?string $viewAnyPermission = 'roles.manage';

    protected static ?string $createPermission = 'roles.manage';

    protected static ?string $updatePermission = 'roles.manage';

    protected static ?string $deletePermission = 'roles.manage';

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoleTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
