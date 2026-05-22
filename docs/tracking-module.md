# Modulo de Tracking GPS

## Objetivo

Implementar tracking GPS en tiempo real para tecnicos con arquitectura limpia sobre Laravel + Filament + Leaflet.

## Capas

- `TrackingController`: endpoints HTTP y vista mobile.
- `TechnicianTrackingService`: ingesta, persistencia y sincronizacion con visitas.
- `TechnicianStatusResolver`: reglas de estado operativo.
- `TechnicianLiveMapService`: proyeccion para mapa vivo e historial.
- `TechnicianLocation`: historial GPS granular.
- `MapaTecnicos`: pagina Filament de oficina.

## Flujo tecnico

1. El tecnico abre `/tracking/mobile`.
2. El navegador pide permiso de geolocalizacion.
3. `watchPosition()` genera posiciones.
4. Cada posicion se encola en `localStorage`.
5. La cola hace `POST /tracking/update`.
6. El backend guarda en `technician_locations` y actualiza snapshot en `users`.
7. La oficina consulta `GET /tracking/live` cada 15 segundos.
8. El mapa dibuja ultima posicion y, al seleccionar un tecnico, consulta `GET /tracking/history/{user}`.

## Estados recomendados

- `offline`: sin heartbeat en 3 minutos.
- `available`: online sin visita activa y sin movimiento fuerte.
- `traveling`: online con movimiento o visita pendiente.
- `working`: visita iniciada con llegada registrada.
- `finished`: visita cerrada recientemente.

## Integracion con visitas

- `ServiceVisitController@start` llama `syncFromVisitStart()`.
- `ServiceVisitController@finish` llama `syncFromVisitFinish()`.
- Fotos de llegada y salida tambien sincronizan estado tecnico.
- `Pendiente` sigue siendo el nucleo: al arrancar se pasa a `in_progress`, al cerrar a `completed`.

## Endpoints

- `GET /tracking/mobile`
- `POST /tracking/update`
- `POST /tracking/status`
- `GET /tracking/live`
- `GET /tracking/history/{user}`

## Frecuencias recomendadas

- Celular enviando: 10 a 20 segundos cuando hay movimiento.
- Celular quieto: 30 a 60 segundos si luego agregas throttling adaptativo.
- Oficina refrescando: 10 a 15 segundos con polling.
- Desconexion: considerar offline tras 180 segundos.

## Recomendacion tiempo real

### Hoy: polling

Para 2 tecnicos el mejor equilibrio es polling:

- menos complejidad operativa
- cero dependencia extra
- estable en Android Chrome
- simple para desarrollo con ngrok

### Futuro: Laravel Reverb

Migrar a eventos + Reverb cuando haya:

- mas de 8/10 tecnicos concurrentes
- necesidad de refresh sub-segundo
- supervisores mirando el mapa en paralelo

La capa de servicios ya separa suficiente logica como para cambiar la entrega en vivo sin redisenar el dominio.

## Seguridad

- rutas protegidas con `auth`
- permisos `tracking.view` y `tracking.mobile`
- rate limiting por usuario/ip
- tecnicos solo usan su sesion autenticada
- admins ven todo

## Evoluciones siguientes

- token por dispositivo
- firma del device id
- throttling adaptativo por bateria
- compactacion historica por franjas de 5 minutos
- geofencing de llegada automatica al cliente
- eventos broadcast con Reverb
