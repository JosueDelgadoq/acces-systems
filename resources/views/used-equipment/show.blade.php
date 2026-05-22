<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $equipment->code }} - Ficha tecnica</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: linear-gradient(180deg, #eef3f8 0%, #f8fafc 100%); color: #0f172a; }
        .wrap { max-width: 1180px; margin: 0 auto; padding: 28px 18px 40px; }
        .hero { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(280px, .9fr); gap: 18px; align-items: start; }
        .card { background: #fff; border: 1px solid #dbe4ee; border-radius: 20px; padding: 22px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.06); min-width: 0; }
        .eyebrow { font-size: 12px; letter-spacing: .12em; text-transform: uppercase; color: #64748b; margin-bottom: 8px; }
        .code { font-size: clamp(28px, 4vw, 42px); font-weight: 800; line-height: 1; margin: 0 0 16px; word-break: break-word; }
        .badges { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
        .badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 7px 12px; font-size: 12px; font-weight: 700; }
        .green { background: #dcfce7; color: #166534; }
        .yellow { background: #fef3c7; color: #92400e; }
        .red { background: #fee2e2; color: #991b1b; }
        .gray { background: #e2e8f0; color: #334155; }
        .meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .meta-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px; min-width: 0; }
        .meta-item strong { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: #64748b; margin-bottom: 7px; }
        .meta-item span { display: block; line-height: 1.45; overflow-wrap: anywhere; }
        .side-card { display: flex; flex-direction: column; gap: 16px; }
        .qr-box { display: flex; justify-content: center; align-items: center; padding: 14px; border: 1px solid #e2e8f0; border-radius: 16px; background: #f8fafc; overflow: hidden; }
        .qr-box svg { width: min(100%, 210px); height: auto; display: block; }
        .url { margin: 0; font-size: 12px; color: #64748b; text-align: center; word-break: break-all; }
        .section { margin-top: 18px; }
        .section-title { font-size: 22px; font-weight: 800; margin: 0 0 14px; }
        .gallery { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; }
        .photo-card { background: #fff; border: 1px solid #dbe4ee; border-radius: 18px; overflow: hidden; box-shadow: 0 16px 35px rgba(15, 23, 42, 0.05); }
        .photo-card img { display: block; width: 100%; height: 220px; object-fit: cover; background: #e2e8f0; }
        .photo-card .caption { padding: 10px 12px; font-size: 12px; color: #64748b; }
        .empty { padding: 18px; border: 1px dashed #cbd5e1; border-radius: 16px; background: rgba(255, 255, 255, 0.75); color: #64748b; }
        .history-card { overflow: hidden; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 720px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
        th { font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }
        td { font-size: 14px; line-height: 1.45; }
        .detail-line { display: block; color: #475569; margin-top: 4px; }
        @media (max-width: 900px) {
            .hero { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .wrap { padding-left: 12px; padding-right: 12px; }
            .card { padding: 16px; border-radius: 16px; }
            .meta { grid-template-columns: 1fr; }
            .photo-card img { height: 190px; }
            table { min-width: 620px; }
        }
    </style>
</head>
<body>
    @php
        $zoneColor = match ($equipment->warehouse_zone) {
            'verde' => 'green',
            'amarilla' => 'yellow',
            'roja' => 'red',
            default => 'gray',
        };
    @endphp

    <div class="wrap">
        <div class="hero">
            <section class="card">
                <div class="eyebrow">ACCESS SYSTEMS · Ficha de equipo usado</div>
                <h1 class="code">{{ $equipment->code }}</h1>

                <div class="badges">
                    <span class="badge {{ $zoneColor }}">{{ $equipment->status_label }}</span>
                    <span class="badge gray">{{ $equipment->type_label }}</span>
                    @if($equipment->brand_label)
                        <span class="badge gray">{{ $equipment->brand_label }}</span>
                    @endif
                </div>

                <div class="meta">
                    <div class="meta-item">
                        <strong>Condicion de ingreso</strong>
                        <span>{{ $equipment->ingress_condition_label ?? 'Sin definir' }}</span>
                    </div>
                    <div class="meta-item">
                        <strong>Ubicacion</strong>
                        <span>{{ $equipment->full_location ?? 'Sin definir' }}</span>
                    </div>
                    <div class="meta-item">
                        <strong>Faltantes</strong>
                        <span>{{ $equipment->missing_parts_labels ? implode(', ', $equipment->missing_parts_labels) : 'Sin faltantes' }}</span>
                    </div>
                    <div class="meta-item">
                        <strong>Observaciones</strong>
                        <span>{{ $equipment->notes ?: 'Sin observaciones' }}</span>
                    </div>
                </div>
            </section>

            <aside class="card side-card">
                <div class="eyebrow">Acceso rapido</div>
                <div class="qr-box">{!! $qrSvg !!}</div>
                <p class="url">{{ $equipment->public_url }}</p>
            </aside>
        </div>

        <section class="section">
            <h2 class="section-title">Imagenes</h2>
            @if(count($photoUrls))
                <div class="gallery">
                    @foreach($photoUrls as $index => $photoUrl)
                        <a class="photo-card" href="{{ $photoUrl }}" target="_blank" rel="noreferrer">
                            <img src="{{ $photoUrl }}" alt="Foto {{ $index + 1 }} de {{ $equipment->code }}">
                            <div class="caption">Foto {{ $index + 1 }}</div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="empty">Este equipo todavia no tiene imagenes cargadas. Ya quedo habilitado el campo para subirlas desde el admin.</div>
            @endif
        </section>

        <section class="section card history-card">
            <h2 class="section-title">Historial</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Movimiento</th>
                            <th>Detalle</th>
                            <th>Responsable</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($equipment->movements as $movement)
                            <tr>
                                <td>{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ str_replace('_', ' ', ucfirst($movement->movement_type)) }}</td>
                                <td>
                                    {{ $movement->description ?: 'Sin detalle' }}
                                    @if($movement->to_status)
                                        <span class="detail-line">Estado: {{ \App\Models\UsedEquipment::STATUS_OPTIONS[$movement->to_status] ?? $movement->to_status }}</span>
                                    @endif
                                    @if($movement->to_location)
                                        <span class="detail-line">Ubicacion: {{ $movement->to_location }}</span>
                                    @endif
                                </td>
                                <td>{{ $movement->user?->name ?? 'Sistema' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">Sin movimientos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html>
