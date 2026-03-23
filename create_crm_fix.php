<?php
// FIX CRM RESOURCES SYNTAX ERRORS

// 1. Delete broken resources
rmdir('app/Filament/Resources/Leads');
rmdir('app/Filament/Resources/Presupuestos');
rmdir('app/Filament/Resources/Ventas');

// 2. Regenerate
exec('php artisan filament:resource Lead --generate');
exec('php artisan filament:resource Presupuesto --generate');
exec('php artisan filament:resource Venta --generate');

// 3. Cache clear
exec('php artisan cache:clear');
exec('php artisan config:clear');
exec('php artisan view:clear');
exec('php artisan route:clear');
exec('php artisan filament:cache-components');

// 4. Test
echo 'CRM FIX COMPLETED. Run: php artisan serve';
?>

