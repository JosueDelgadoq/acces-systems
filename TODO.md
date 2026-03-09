# Plan de Implementación - ERP Postventa

## Proceso 1: HABILITACIONES (Prioridad ALTA)
### Tabla: `habilitations`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | Primary Key |
| client_id | foreignId | Cliente |
| equipment | string | Equipo a habilitar |
| status | enum | pendiente, documentacion, enviado_gestor, en_tramite, aprobado, rechazado, archivado |
| doc_completa | boolean | Documentación completa |
| fecha_envio_gestor | date | Fecha envío al gestor |
| fecha_presentacion | date | Fecha presentación |
| proxima_gestion | date | Próxima gestión |
| observaciones | text | Observaciones |
| created_at, updated_at | timestamps | |

### Recursos Filament:
- `app/Filament/Resources/Habilitations/HabilitationResource.php`
- `app/Filament/Resources/Habilitations/Schemas/HabilitationForm.php`
- `app/Filament/Resources/Habilitations/Tables/HabilitationsTable.php`
- `app/Filament/Resources/Habilitations/Pages/ListHabilitations.php`
- `app/Filament/Resources/Habilitations/Pages/CreateHabilitation.php`
- `app/Filament/Resources/Habilitations/Pages/EditHabilitation.php`

---

## Proceso 2: PRESUPUESTOS DE VISITAS TÉCNICAS (Prioridad ALTA)
### Tabla: `technical_budgets`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | Primary Key |
| client_id | foreignId | Cliente |
| title | string | Título del presupuesto |
| description | text | Descripción del servicio |
| status | enum | nuevo, cotizado, enviado, aprobado, rechazado, visita_coordinada, cerrado |
| amount | decimal | Monto presupuestado |
| sent_at | datetime | Fecha de envío |
| approval_date | date | Fecha de aprobación |
| scheduled_visit | date | Fecha de visita programada |
| service_remito | string | Número de remito de servicio |
| created_at, updated_at | timestamps | |

### Recursos Filament:
- `app/Filament/Resources/TechnicalBudgets/TechnicalBudgetResource.php`
- Similar estructura a Claims

---

## Proceso 3: LOGÍSTICA DE EQUIPOS (Prioridad MEDIA)
### Tabla: `equipment_deliveries`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | Primary Key |
| client_id | foreignId | Cliente |
| equipment | string | Equipo |
| sale_date | date | Fecha de venta |
| delivery_date | date | Fecha de entrega |
| installation_date | date | Fecha de instalación |
| delivery_remito | string | Remito de entrega |
| installation_remito | string | Remito de instalación |
| signed_remito | boolean | Remito firmado |
| status | enum | pendiente_confirmacion, confirmado, flete_solicitado, en_camino, entregado, instalado |
| observations | text | Observaciones |
| created_at, updated_at | timestamps | |

---

## Proceso 4: FACTURACIÓN (Prioridad MEDIA)
### Tabla: `billing_controls`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | Primary Key |
| client_id | foreignId | Cliente |
| service_description | string | Descripción del servicio |
| amount | decimal | Monto |
| invoice_number | string | Número de factura |
| invoice_date | date | Fecha de factura |
| invoiced | boolean | ¿Se facturó? |
| paid | boolean | ¿Se cobró? |
| payment_date | date | Fecha de cobro |
| observations | text | Observaciones |
| created_at, updated_at | timestamps | |

---

## Proceso 5: COMPRA DE REPUESTOS (Prioridad BAJA)
### Tabla: `parts_orders`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | Primary Key |
| technician_id | foreignId | Técnico que solicita |
| part_name | string | Nombre del repuesto |
| supplier | string | Proveedor |
| estimated_cost | decimal | Costo estimado |
| status | enum | pendiente_cotizacion, cotizado, aprobado, comprado, recibido, entregado_tecnico |
| purchase_date | date | Fecha de compra |
| arrival_date | date | Fecha de llegada estimada |
| invoice | string | Factura del proveedor |
| observations | text | Observaciones |
| created_at, updated_at | timestamps | |

---

## Orden de implementación sugerido:
1. ✅ HABILITACIONES - "Nada sale si no está en esta planilla" - ✅ COMPLETADO
2. ✅ PRESUPUESTOS - Segunda prioridad (genera ingresos) - ✅ COMPLETADO
3. ✅ LOGÍSTICA - Tercera prioridad (entregas) - ✅ COMPLETADO
4. ✅ FACTURACIÓN - Control pero no genera - ✅ COMPLETADO
5. ✅ REPUESTOS - Ultimo (apoyo a operaciones) - ✅ COMPLETADO

---

## Recursos creados:
- **Habilitations** → app/Filament/Resources/Habilitations/
- **TechnicalBudgets** → app/Filament/Resources/TechnicalBudgets/
- **EquipmentDeliveries** → app/Filament/Resources/EquipmentDeliveries/
- **BillingControls** → app/Filament/Resources/BillingControls/
- **PartsOrders** → app/Filament/Resources/PartsOrders/

