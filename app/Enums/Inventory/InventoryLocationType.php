<?php

namespace App\Enums\Inventory;

enum InventoryLocationType: string
{
    case WAREHOUSE = 'warehouse';
    case TECHNICIAN = 'technician';
    case CLIENT_SITE = 'client_site';
    case INSTALLATION = 'installation';
    case TRANSIT = 'transit';
    case SERVICE = 'service';

    public function label(): string
    {
        return match ($this) {
            self::WAREHOUSE => 'Depósito',
            self::TECHNICIAN => 'Técnico',
            self::CLIENT_SITE => 'Cliente',
            self::INSTALLATION => 'Instalación',
            self::TRANSIT => 'Tránsito',
            self::SERVICE => 'Servicio',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
