<?php

namespace App\Support;

use App\Support\Modules\InventarioPermissions;
use App\Support\Modules\LeadPermissions;
use Spatie\Permission\Models\Permission;

class PermissionCatalog
{
    public static function groups(): array
    {
        return [
            'administracion' => [
                'label' => 'Administracion',
                'permissions' => [
                    'user.manage' => 'Administrar usuarios',
                    'roles.manage' => 'Administrar roles y permisos',
                ],
            ],
            'dashboard' => [
                'label' => 'Dashboard',
                'permissions' => [
                    'dashboard.view' => 'Ver dashboard',
                ],
            ],
            'comercial' => [
                'label' => 'Comercial',
                'permissions' => [
                    ...LeadPermissions::options(),
                    'presupuesto.view' => 'Ver presupuestos',
                    'presupuesto.create' => 'Crear presupuestos',
                    'presupuesto.update' => 'Editar presupuestos',
                    'presupuesto.delete' => 'Eliminar presupuestos',
                    'presupuesto.send' => 'Enviar presupuestos',
                    'venta.view' => 'Ver ventas',
                    'venta.create' => 'Crear ventas',
                    'venta.update' => 'Editar ventas',
                    'venta.delete' => 'Eliminar ventas',
                    'venta.close' => 'Cerrar ventas',
                ],
            ],
            'clientes' => [
                'label' => 'Clientes y postventa',
                'permissions' => [
                    'cliente.view' => 'Ver clientes',
                    'cliente.create' => 'Crear clientes',
                    'cliente.update' => 'Editar clientes',
                    'cliente.delete' => 'Eliminar clientes',
                    'claim.view' => 'Ver reclamos',
                    'claim.create' => 'Crear reclamos',
                    'claim.update' => 'Editar reclamos',
                    'claim.delete' => 'Eliminar reclamos',
                    'conservation.view' => 'Ver conservaciones',
                    'conservation.create' => 'Crear conservaciones',
                    'conservation.update' => 'Editar conservaciones',
                    'conservation.delete' => 'Eliminar conservaciones',
                ],
            ],
            'operaciones' => [
                'label' => 'Operaciones',
                'permissions' => [
                    'pendiente.view' => 'Ver pendientes',
                    'pendiente.create' => 'Crear pendientes',
                    'pendiente.update' => 'Editar pendientes',
                    'pendiente.delete' => 'Eliminar pendientes',
                    'pendiente.assign' => 'Asignar pendientes',
                    'technical_board.view' => 'Ver tablero tecnico',
                    'technical_board.update' => 'Gestionar tablero tecnico',
                    'tracking.view' => 'Ver tracking y mapas',
                    'tracking.mobile' => 'Usar tracking mobile',
                ],
            ],
            'control' => [
                'label' => 'Control y servicio',
                'permissions' => [
                    'habilitation.view' => 'Ver habilitaciones',
                    'habilitation.create' => 'Crear habilitaciones',
                    'habilitation.update' => 'Editar habilitaciones',
                    'habilitation.delete' => 'Eliminar habilitaciones',
                    'technical_budget.view' => 'Ver presupuestos tecnicos',
                    'technical_budget.create' => 'Crear presupuestos tecnicos',
                    'technical_budget.update' => 'Editar presupuestos tecnicos',
                    'technical_budget.delete' => 'Eliminar presupuestos tecnicos',
                    'equipment_delivery.view' => 'Ver entregas',
                    'equipment_delivery.create' => 'Crear entregas',
                    'equipment_delivery.update' => 'Editar entregas',
                    'equipment_delivery.delete' => 'Eliminar entregas',
                    'billing_control.view' => 'Ver controles de facturacion',
                    'billing_control.create' => 'Crear controles de facturacion',
                    'billing_control.update' => 'Editar controles de facturacion',
                    'billing_control.delete' => 'Eliminar controles de facturacion',
                    'parts_order.view' => 'Ver pedidos de repuestos',
                    'parts_order.create' => 'Crear pedidos de repuestos',
                    'parts_order.update' => 'Editar pedidos de repuestos',
                    'parts_order.delete' => 'Eliminar pedidos de repuestos',
                ],
            ],
            'reportes' => [
                'label' => 'Reportes',
                'permissions' => [
                    'reportes.view' => 'Ver informes y reportes',
                ],
            ],
            'inventario' => [
                'label' => 'Inventario',
                'permissions' => InventarioPermissions::options(),
            ],
        ];
    }

    public static function all(): array
    {
        return collect(static::groups())
            ->pluck('permissions')
            ->reduce(fn (array $carry, array $permissions): array => $carry + $permissions, []);
    }

    public static function groupedOptionsFromDatabase(): array
    {
        $groups = static::groups();
        $knownPermissions = array_keys(static::all());

        $unknownPermissions = \Spatie\Permission\Models\Permission::query()
            ->pluck('name')
            ->reject(fn (string $permission): bool => in_array($permission, $knownPermissions, true))
            ->sort()
            ->mapWithKeys(fn (string $permission): array => [
                $permission => static::humanize($permission),
            ])
            ->all();

        if ($unknownPermissions !== []) {
            $groups['otros'] = [
                'label' => 'Otros',
                'permissions' => $unknownPermissions,
            ];
        }

        return $groups;
    }

    public static function extractSelectedPermissions(array $data): array
    {
        return collect($data)
            ->filter(fn (mixed $value, string $key): bool => str_starts_with($key, 'permissions_'))
            ->flatten()
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function ensurePermissionsExist(array $permissions, ?string $guardName = null): array
    {
        $guardName ??= config('auth.defaults.guard', 'web');

        return collect($permissions)
            ->filter(fn (mixed $permission): bool => filled($permission))
            ->map(fn (mixed $permission): string => (string) $permission)
            ->unique()
            ->values()
            ->tap(function ($permissions) use ($guardName): void {
                $permissions->each(
                    fn (string $permission) => Permission::findOrCreate($permission, $guardName),
                );
            })
            ->all();
    }

    public static function expandPermissionsForForm(array $permissions): array
    {
        $data = [];

        $groupPermissions = collect(static::groups())
            ->mapWithKeys(fn (array $group, string $key): array => [
                $key => array_keys($group['permissions']),
            ]);

        foreach ($permissions as $permission) {
            $group = $groupPermissions->search(
                fn (array $items): bool => in_array($permission, $items, true),
            ) ?: 'otros';

            $data["permissions_{$group}"][] = $permission;
        }

        return $data;
    }

    public static function humanize(string $permission): string
    {
        return (string) str($permission)->replace(['.', '_'], ' ')->headline();
    }

    public static function defaultsByRole(): array
    {
        return [
            'admin' => array_keys(static::all()),
            'gerente' => [
                'dashboard.view',
                'reportes.view',
                LeadPermissions::VIEW,
                LeadPermissions::VIEW_ALL,
                LeadPermissions::UPDATE,
                LeadPermissions::ASSIGN,
                LeadPermissions::CHANGE_STATUS,
                'presupuesto.view',
                'presupuesto.update',
                'presupuesto.send',
                'venta.view',
                'venta.update',
                'venta.close',
                'cliente.view',
                'claim.view',
                'conservation.view',
                'pendiente.view',
                'pendiente.assign',
                'technical_board.view',
                'tracking.view',
                InventarioPermissions::VIEW,
                InventarioPermissions::VIEW_ALL,
            ],
            'comercial' => [
                'dashboard.view',
                LeadPermissions::VIEW,
                LeadPermissions::VIEW_ALL,
                LeadPermissions::CREATE,
                LeadPermissions::UPDATE,
                LeadPermissions::ASSIGN,
                LeadPermissions::CHANGE_STATUS,
                'presupuesto.view',
                'presupuesto.create',
                'presupuesto.update',
                'presupuesto.send',
                'venta.view',
                'venta.create',
                'cliente.view',
            ],
            'tecnico' => [
                'dashboard.view',
                'pendiente.view',
                'technical_board.view',
                'technical_board.update',
                'tracking.mobile',
                InventarioPermissions::VIEW,
            ],
            'administrativo' => [
                'dashboard.view',
                'cliente.view',
                'cliente.update',
                'venta.view',
                'venta.create',
                'venta.update',
                'equipment_delivery.view',
                'equipment_delivery.create',
                'equipment_delivery.update',
                'billing_control.view',
                'billing_control.create',
                'billing_control.update',
                InventarioPermissions::VIEW,
            ],
        ];
    }
}
