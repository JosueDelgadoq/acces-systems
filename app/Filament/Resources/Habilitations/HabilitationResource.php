<?php

namespace App\Filament\Resources\Habilitations;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\Habilitations\Pages\CreateHabilitation;
use App\Filament\Resources\Habilitations\Pages\EditHabilitation;
use App\Filament\Resources\Habilitations\Pages\ListHabilitations;
use App\Filament\Resources\Habilitations\Schemas\HabilitationForm;
use App\Filament\Resources\Habilitations\Tables\HabilitationsTable;
use App\Models\Habilitation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HabilitationResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = Habilitation::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?string $navigationLabel = 'Habilitaciones';

    protected static ?string $modelLabel = 'Habilitacion';

    protected static ?string $pluralModelLabel = 'Habilitaciones';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'equipment';

    protected static string | UnitEnum | null $navigationGroup = 'Gestion';

    protected static ?string $navigationPermission = 'habilitation.view';

    protected static ?string $viewAnyPermission = 'habilitation.view';

    protected static ?string $createPermission = 'habilitation.create';

    protected static ?string $updatePermission = 'habilitation.update';

    protected static ?string $deletePermission = 'habilitation.delete';

    public static function form(Schema $schema): Schema
    {
        return HabilitationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HabilitationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHabilitations::route('/'),
            'create' => CreateHabilitation::route('/create'),
            'edit' => EditHabilitation::route('/{record}/edit'),
        ];
    }
}
