$headers = @{
    "Authorization" = "token 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"
    "Accept" = "application/vnd.github.v3+json"
}

$body = @{
    "title" = "feat: Implementar 5 procesos del ERP de postventa"
    "body" = @"
## Resumen de cambios

Se implementan los 5 procesos principales del ERP de postventa:

### 1. HABILITACIONES
- Tabla `habilitations` para seguimiento de documentación
- Estados: pendiente, documentacion, enviado_gestor, en_tramite, aprobado, rechazado, archivado

### 2. PRESUPUESTOS DE VISITAS TÉCNICAS
- Tabla `technical_budgets` para cotizaciones
- Estados: nuevo, cotizado, enviado, aprobado, rechazado, visita_coordinada, cerrado

### 3. LOGÍSTICA DE EQUIPOS
- Tabla `equipment_deliveries` para control de entregas
- Control de fechas: venta, entrega, instalación

### 4. FACTURACIÓN
- Tabla `billing_controls` para control de facturación

### 5. COMPRA DE REPUESTOS
- Tabla `parts_orders` para control de pedidos

### Automatizaciones
- Observers para workflows automáticos
- Job para verificación diaria de fechas
"@
    "base" = "master"
    "head" = "blackboxai/feature/erp-processes"
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "https://api.github.com/repos/JosueDelgadoq/acces-systems/pulls" -Method Post -Headers $headers -Body $body -ContentType "application/json"

Write-Output $response.html_url
