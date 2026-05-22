<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionControlledPage;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class MapaClientes extends Page
{
    use HasPermissionControlledPage;

    protected static ?string $permission = 'tracking.view';

    protected static ?string $title = 'Mapa de Clientes';

    protected ?string $heading = 'Mapa de Clientes';

    protected string $view = 'filament.pages.mapa-clientes';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Mapa';

    protected static string | UnitEnum | null $navigationGroup = 'BLOQUE B - Operaciones';

    protected static ?string $slug = 'mapa-clientes';
}
