<?php

namespace App\Filament\Resources\Users;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static ?int $navigationSort = 0;

    protected static string|UnitEnum|null $navigationGroup = 'BLOQUE A - Admin';

    protected static ?string $navigationPermission = 'user.manage';

    protected static ?string $viewAnyPermission = 'user.manage';

    protected static ?string $createPermission = 'user.manage';

    protected static ?string $updatePermission = 'user.manage';

    protected static ?string $deletePermission = 'user.manage';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
