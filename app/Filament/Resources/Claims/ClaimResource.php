<?php

namespace App\Filament\Resources\Claims;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\Claims\Pages\CreateClaim;
use App\Filament\Resources\Claims\Pages\EditClaim;
use App\Filament\Resources\Claims\Pages\ListClaims;
use App\Filament\Resources\Claims\Schemas\ClaimForm;
use App\Filament\Resources\Claims\Tables\ClaimsTable;
use App\Models\Claim;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ClaimResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = Claim::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Reclamos';

    protected static ?string $modelLabel = 'Reclamo';

    protected static ?string $pluralModelLabel = 'Reclamos';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string | UnitEnum | null $navigationGroup = 'Gestion';

    protected static ?string $navigationPermission = 'claim.view';

    protected static ?string $viewAnyPermission = 'claim.view';

    protected static ?string $createPermission = 'claim.create';

    protected static ?string $updatePermission = 'claim.update';

    protected static ?string $deletePermission = 'claim.delete';

    public static function form(Schema $schema): Schema
    {
        return ClaimForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClaimsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClaims::route('/'),
            'create' => CreateClaim::route('/create'),
            'edit' => EditClaim::route('/{record}/edit'),
        ];
    }
}
