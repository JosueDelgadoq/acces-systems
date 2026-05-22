<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tracking tecnico</title>
    <style>
        body { margin: 0; font-family: system-ui, sans-serif; background: #0f172a; color: white; }
        .mobile-shell { min-height: 100vh; padding: 1rem; background: linear-gradient(180deg, #0f172a, #1e293b); }
        .mobile-card { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12); border-radius: 1.25rem; padding: 1rem; backdrop-filter: blur(12px); }
        .mobile-title { font-size: 1.2rem; font-weight: 800; margin: 0; }
        .mobile-meta { margin-top: .35rem; color: #cbd5e1; font-size: .9rem; }
        .mobile-grid { display: grid; gap: .75rem; margin-top: 1rem; }
        .mobile-pill { display: inline-flex; align-items: center; gap: .45rem; padding: .45rem .8rem; border-radius: 999px; background: rgba(255,255,255,.1); font-size: .85rem; }
        .mobile-btn { width: 100%; border: 0; border-radius: 1rem; padding: .95rem 1rem; font-size: .95rem; font-weight: 800; cursor: pointer; }
        .mobile-btn-primary { background: #10b981; color: #052e16; }
        .mobile-btn-danger { background: #f87171; color: #450a0a; }
        .mobile-log { margin-top: 1rem; font-size: .82rem; color: #cbd5e1; white-space: pre-line; }
    </style>
</head>
<body>
    <div class="mobile-shell">
        <div class="mobile-card">
            <h1 class="mobile-title">Tracking GPS tecnico</h1>
            <div class="mobile-meta">{{ auth()->user()->name }}</div>

            <div class="mobile-grid">
                <div class="mobile-pill">Estado: <strong id="tracking-state">Detenido</strong></div>
                <div class="mobile-pill">GPS: <strong id="gps-state">Sin permiso</strong></div>
                <div class="mobile-pill">Cola offline: <strong id="queue-size">0</strong></div>
                <div class="mobile-pill">Ultimo envio: <strong id="last-sync">Nunca</strong></div>
            </div>

            <div class="mobile-grid">
                <button id="start-tracking" type="button" class="mobile-btn mobile-btn-primary">Iniciar tracking</button>
                <button id="stop-tracking" type="button" class="mobile-btn mobile-btn-danger">Detener tracking</button>
            </div>

            <div id="tracking-log" class="mobile-log">Listo para iniciar.</div>
        </div>
    </div>

    <script>
        (() => {
            const STORAGE_KEY = 'erp_tracking_queue_v1';
            const state = {
                watchId: null,
                running: false,
                queue: JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'),
                flushTimer: null,
                lastPosition: null,
                flushInProgress: false,
            };

            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            function log(message) {
                const element = document.getElementById('tracking-log');
                const line = `[${new Date().toLocaleTimeString('es-AR')}] ${message}`;
                element.textContent = `${line}\n${element.textContent}`.trim();
            }

            function updateUi() {
                document.getElementById('tracking-state').textContent = state.running ? 'Activo' : 'Detenido';
                document.getElementById('queue-size').textContent = String(state.queue.length);
            }

            function persistQueue() {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(state.queue.slice(-200)));
                updateUi();
            }

            function setGpsState(value) {
                document.getElementById('gps-state').textContent = value;
            }

            function setLastSync(value) {
                document.getElementById('last-sync').textContent = value;
            }

            async function sendStatus(enabled) {
                await fetch('/tracking/status', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'ngrok-skip-browser-warning': 'true',
                    },
                    body: JSON.stringify({ enabled }),
                });
            }

            async function flushQueue() {
                if (state.flushInProgress || !navigator.onLine || !state.queue.length) {
                    return;
                }

                state.flushInProgress = true;

                try {
                    const pending = [...state.queue];

                    for (const payload of pending) {
                        try {
                            const response = await fetch('/tracking/update', {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'ngrok-skip-browser-warning': 'true',
                                },
                                body: JSON.stringify(payload),
                            });

                            if (!response.ok) {
                                const responseText = await response.text();
                                const snippet = responseText
                                    ? ` - ${responseText.replace(/\s+/g, ' ').slice(0, 180)}`
                                    : '';

                                throw new Error(`HTTP ${response.status}${snippet}`);
                            }

                            state.queue.shift();
                            persistQueue();
                            setLastSync(new Date().toLocaleTimeString('es-AR'));
                        } catch (error) {
                            log(`No se pudo enviar la posicion. Queda en cola. ${error.message}`);
                            break;
                        }
                    }
                } finally {
                    state.flushInProgress = false;
                }
            }

            function queuePayload(position) {
                const coords = position.coords;
                const payload = {
                    lat: Number(coords.latitude.toFixed(7)),
                    lng: Number(coords.longitude.toFixed(7)),
                    accuracy: coords.accuracy ? Number(coords.accuracy.toFixed(2)) : null,
                    speed: Number.isFinite(coords.speed) && coords.speed !== null ? Number(coords.speed.toFixed(2)) : null,
                    is_moving: Number.isFinite(coords.speed) && coords.speed !== null ? coords.speed >= 1.2 : null,
                    tracked_at: new Date(position.timestamp).toISOString(),
                };

                state.lastPosition = payload;
                state.queue.push(payload);
                persistQueue();
                flushQueue();
            }

            function startTracking() {
                if (!('geolocation' in navigator)) {
                    log('Este dispositivo no soporta geolocalizacion.');
                    return;
                }

                if (state.running) {
                    return;
                }

                navigator.geolocation.getCurrentPosition(() => {
                    setGpsState('Permitido');
                }, () => {
                    setGpsState('Denegado');
                }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });

                state.watchId = navigator.geolocation.watchPosition((position) => {
                    setGpsState('Activo');
                    queuePayload(position);
                }, (error) => {
                    log(`GPS error: ${error.message}`);
                    setGpsState('Error');
                }, {
                    enableHighAccuracy: true,
                    timeout: 20000,
                    maximumAge: 15000,
                });

                state.running = true;
                updateUi();
                sendStatus(true).catch(() => {});
                state.flushTimer = window.setInterval(flushQueue, 12000);
                log('Tracking iniciado.');
            }

            function stopTracking() {
                if (state.watchId !== null) {
                    navigator.geolocation.clearWatch(state.watchId);
                    state.watchId = null;
                }

                if (state.flushTimer) {
                    clearInterval(state.flushTimer);
                    state.flushTimer = null;
                }

                state.running = false;
                updateUi();
                sendStatus(false).catch(() => {});
                log('Tracking detenido.');
            }

            window.addEventListener('online', flushQueue);
            document.getElementById('start-tracking').addEventListener('click', startTracking);
            document.getElementById('stop-tracking').addEventListener('click', stopTracking);

            updateUi();
            flushQueue();
        })();
    </script>
</body>
</html>
