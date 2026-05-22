<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionControlledPage;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ScanStock extends Page
{
    use HasPermissionControlledPage;

    protected static ?string $permission = 'inventario.update';

    protected string $view = 'filament.pages.scan-stock';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $title = 'Escaner de unidades';

    protected static string | UnitEnum | null $navigationGroup = 'Stock';

    protected static ?string $slug = 'scan-stock';
}
