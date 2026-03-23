import urllib.request
import urllib.parse
import json

token = "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"
owner = "JosueDelgadoq"
repo = "acces-systems"
base = "master"
head = "blackboxai/feature/erp-processes"

url = f"https://api.github.com/repos/{owner}/{repo}/pulls"

data = {
    "title": "feat: Implementar 5 procesos del ERP de postventa",
    "body": """## Resumen de cambios

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
""",
    "base": base,
    "head": head
}

headers = {
    "Authorization": f"token {token}",
    "Accept": "application/vnd.github.v3+json",
    "Content-Type": "application/json"
}

req = urllib.request.Request(url, data=json.dumps(data).encode('utf-8'), headers=headers, method='POST')

try:
    with urllib.request.urlopen(req) as response:
        result = json.loads(response.read().decode('utf-8'))
        print(f"PR creado exitosamente!")
        print(f"URL: {result['html_url']}")
except urllib.error.HTTPError as e:
    error = json.loads(e.read().decode('utf-8'))
    print(f"Error: {e.code}")
    print(error.get('message', 'Unknown error'))
