# ERP Access Systems

ERP interno sobre Laravel 12, Filament 5 y Livewire 4 para operar comercial, postventa, servicio tecnico, tracking y stock.

## Modulos

- Comercial: leads, seguimientos, presupuestos, ventas, metricas y alertas.
- Operaciones: pendientes, tablero tecnico, calendario operativo y visitas.
- Clientes y postventa: clientes, reclamos, conservaciones y habilitaciones.
- Tracking: mapa de tecnicos, historial GPS y vista mobile.
- Inventario: productos, variantes, repuestos y movimientos de stock.

## Requisitos

- PHP 8.2+
- Composer 2
- Node.js 20+
- MySQL o SQLite

## Instalacion

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Para desarrollo local:

```bash
composer run dev
```

## Comandos utiles

```bash
php artisan test
php artisan clients:geocode-map --limit=50
php artisan alerts:process
php artisan alerts:notify-stale-leads
php artisan schedule:work
```

## Notas operativas

- El acceso al panel requiere roles o permisos cargados con Spatie Permission.
- Los endpoints de tracking y mapas estan protegidos con autenticacion y rate limiting.
- El escaner de stock ya no crea productos placeholder automaticamente.
- Los IDs CRM de leads se generan con bloqueo para reducir colisiones.
