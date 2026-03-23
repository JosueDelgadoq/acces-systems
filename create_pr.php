<?php

$token = "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";
$owner = "JosueDelgadoq";
$repo = "acces-systems";

$url = "https://api.github.com/repos/$owner/$repo/pulls";

$body = <<<EOT
## Resumen de cambios

Se implementan los 5 procesos principales del ERP de postventa:

### 1. HABILITACIONES
- Tabla habilitations para seguimiento de documentacion
- Estados: pendiente, documentacion, enviado_gestor, en_tramite, aprobado, rechazado, archivado
- Control de fechas: envio al gestor, presentacion, proxima gestion

### 2. PRESUPUESTOS DE VISITAS TECNICAS
- Tabla technical_budgets para cotizaciones
- Estados: nuevo, cotizado, enviado, aprobado, rechazado, visita_coordinada, cerrado
- Seguimiento de aprobacion y visitas

### 3. LOGISTICA DE EQUIPOS
- Tabla equipment_deliveries para control de entregas
- Control de fechas: venta, entrega, instalacion
- Remitos de entrega e instalacion

### 4. FACTURACION
- Tabla billing_controls para control de facturacion
- Seguimiento de facturas y cobros

### 5. COMPRA DE REPUESTOS
- Tabla parts_orders para control de pedidos
- Estados: pendiente_cotizacion, cotizado, aprobado, comprado, recibido

### Automatizaciones
- Observers para workflows automaticos
- Job para verificacion diaria de fechas
- Registro en ObserverServiceProvider
EOT;

$data = json_encode([
    "title" => "feat: Implementar 5 procesos del ERP de postventa",
    "body" => $body,
    "base" => "master",
    "head" => "blackboxai/feature/erp-processes"
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: token $token",
    "Accept: application/vnd.github.v3+json",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (isset($result['html_url'])) {
    echo "PR creado exitosamente!\n";
    echo "URL: " . $result['html_url'] . "\n";
} else {
    echo "Error: ";
    print_r($result);
}
