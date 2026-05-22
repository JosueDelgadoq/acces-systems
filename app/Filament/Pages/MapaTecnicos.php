<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionControlledPage;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class MapaTecnicos extends Page
{
    use HasPermissionControlledPage;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $permission = 'tracking.view';

    protected string $view = 'filament.pages.mapa-tecnicos';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Mapa de tecnicos';

    protected static string | UnitEnum | null $navigationGroup = 'BLOQUE B - Operaciones';

    protected static ?string $slug = 'mapa-tecnicos';
}
