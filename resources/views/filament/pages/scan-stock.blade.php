@php
    $statusOptions = \App\Models\ProductoUnidad::STATUS_OPTIONS;
@endphp

<x-filament::page>
    <div class="space-y-6">
        <section class="erp-surface-card p-5">
            <div class="flex flex-col gap-2">
                <h2 class="text-xl font-semibold">Escaner de unidades</h2>
                <p class="text-sm text-gray-600">
                    Escanea una unidad serializada para ver su estado actual y registrar el cambio operativo con trazabilidad.
                </p>
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <button type="button" onclick="iniciarScanner()" class="rounded bg-blue-600 px-4 py-2 text-white">
                    Iniciar escaneo
                </button>
                <button type="button" onclick="detenerScanner()" class="rounded bg-gray-600 px-4 py-2 text-white">
                    Detener
                </button>
            </div>

            <div id="reader" class="mt-5 max-w-sm"></div>
        </section>

        <section class="erp-surface-card p-5">
            <div class="flex flex-col gap-2">
                <h3 class="text-lg font-semibold">Unidad escaneada</h3>
                <p class="text-sm text-gray-600">
                    El resultado muestra la unidad, la variante asociada y el stock disponible real de esa variante.
                </p>
            </div>

            <div id="resultado" class="mt-4 rounded border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600">
                Aun no se escaneo ninguna unidad.
            </div>
        </section>

        <section class="erp-surface-card p-5">
            <div class="flex flex-col gap-2">
                <h3 class="text-lg font-semibold">Registrar movimiento</h3>
                <p class="text-sm text-gray-600">
                    Cambia el estado de la unidad. Cada cambio queda auditado con usuario, motivo y observaciones.
                </p>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium text-gray-700">Estado destino</span>
                    <select id="estadoDestino" class="rounded border border-gray-300 p-2">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium text-gray-700">Motivo</span>
                    <input
                        type="text"
                        id="motivoMovimiento"
                        class="rounded border border-gray-300 p-2"
                        placeholder="Ej: instalacion, devolucion, regularizacion"
                    >
                </label>

                <label class="flex flex-col gap-2 md:col-span-2">
                    <span class="text-sm font-medium text-gray-700">Observaciones</span>
                    <textarea
                        id="observacionesMovimiento"
                        rows="3"
                        class="rounded border border-gray-300 p-2"
                        placeholder="Detalle operativo del movimiento"
                    ></textarea>
                </label>
            </div>

            <div class="mt-4">
                <button type="button" onclick="registrarMovimiento()" class="rounded bg-green-600 px-4 py-2 text-white">
                    Registrar cambio
                </button>
            </div>
        </section>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/html5-qrcode"></script>
        <script>
            let scanner = null;
            let unidadActual = null;

            function renderResultado(data) {
                unidadActual = data.scan_type === 'unit' ? data : null;

                const estado = data.estado_label ?? data.estado ?? '-';
                const advertencia = data.scan_type === 'variant'
                    ? '<div class="mt-3 rounded bg-amber-50 p-3 text-amber-700">Este codigo pertenece a una variante. Para movimientos operativos, escanea una unidad serializada.</div>'
                    : '';

                document.getElementById('resultado').innerHTML = `
                    <div class="space-y-2">
                        <div><strong>Producto:</strong> ${data.producto ?? '-'}</div>
                        <div><strong>Tipo:</strong> ${data.tipo ?? '-'}</div>
                        <div><strong>Modelo:</strong> ${data.modelo ?? '-'}</div>
                        <div><strong>Codigo:</strong> ${data.codigo_barra ?? 'Sin codigo serial'}</div>
                        <div><strong>Estado actual:</strong> ${estado}</div>
                        <div><strong>Disponibles de la variante:</strong> ${data.stock_disponible ?? data.stock ?? 0}</div>
                        <div><strong>Total de unidades:</strong> ${data.total_unidades ?? '-'}</div>
                        ${advertencia}
                    </div>
                `;

                if (unidadActual?.estado) {
                    document.getElementById('estadoDestino').value = unidadActual.estado;
                }
            }

            async function registrarMovimiento() {
                if (!unidadActual?.producto_unidad_id) {
                    alert('Escanea una unidad serializada antes de registrar un cambio.');
                    return;
                }

                const estadoDestino = document.getElementById('estadoDestino').value;
                const motivo = document.getElementById('motivoMovimiento').value;
                const observaciones = document.getElementById('observacionesMovimiento').value;

                const response = await fetch('/movimiento', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        producto_unidad_id: unidadActual.producto_unidad_id,
                        estado_destino: estadoDestino,
                        motivo: motivo,
                        observaciones: observaciones,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    alert(data.message ?? 'No se pudo registrar el movimiento.');
                    return;
                }

                alert(data.message ?? 'Movimiento registrado.');
                renderResultado(data.unit);
            }

            async function onScanSuccess(codigo) {
                const response = await fetch('/scan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ codigo_barra: codigo }),
                });

                const data = await response.json();

                if (!response.ok) {
                    alert(data.message ?? 'No se encontro el codigo escaneado.');
                    return;
                }

                renderResultado(data);
            }

            function iniciarScanner() {
                if (!scanner) {
                    scanner = new Html5Qrcode('reader');
                }

                scanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: 250 },
                    onScanSuccess
                ).catch(() => {
                    alert('No se pudo abrir la camara.');
                });
            }

            function detenerScanner() {
                if (!scanner) {
                    return;
                }

                scanner.stop().then(() => {
                    document.getElementById('reader').innerHTML = '';
                });
            }
        </script>
    @endpush
</x-filament::page>
