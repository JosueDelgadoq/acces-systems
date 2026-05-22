<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $equipment->code }} - Etiqueta</title>
    <style>
        body { margin: 0; background: #eef2f7; font-family: Arial, sans-serif; }
        .sheet { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px; }
        .label { width: 10cm; min-height: 7cm; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 18px 40px rgba(15, 23, 42, .15); display: grid; grid-template-columns: 12px 1fr 130px; }
        .bar.green { background: #16a34a; }
        .bar.yellow { background: #eab308; }
        .bar.red { background: #dc2626; }
        .bar.gray { background: #64748b; }
        .content { padding: 16px 14px; }
        .company { font-size: 12px; letter-spacing: .08em; color: #64748b; text-transform: uppercase; }
        .code { font-size: 28px; font-weight: 900; line-height: 1; margin: 8px 0 12px; }
        .row { margin-bottom: 8px; font-size: 13px; }
        .row strong { display: inline-block; min-width: 86px; }
        .qr { padding: 14px; display: flex; align-items: center; justify-content: center; background: #f8fafc; }
        .qr svg { width: 100%; height: auto; }
        @media print { body { background: #fff; } .sheet { padding: 0; } .label { box-shadow: none; border-radius: 0; } }
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

    <div class="sheet">
        <div class="label">
            <div class="bar {{ $zoneColor }}"></div>
            <div class="content">
                <div class="company">ACCESS SYSTEMS</div>
                <div class="code">{{ $equipment->code }}</div>
                <div class="row"><strong>Marca:</strong> {{ $equipment->brand_label ?? 'Sin definir' }}</div>
                <div class="row"><strong>Estado:</strong> {{ $equipment->status_label }}</div>
                <div class="row"><strong>Ingreso:</strong> {{ $equipment->ingress_condition_label ?? 'Sin definir' }}</div>
                <div class="row"><strong>Faltantes:</strong> {{ $equipment->missing_parts_labels ? implode(', ', $equipment->missing_parts_labels) : 'Sin faltantes' }}</div>
                <div class="row"><strong>Ubicación:</strong> {{ $equipment->full_location ?? 'Sin definir' }}</div>
            </div>
            <div class="qr">{!! $qrSvg !!}</div>
        </div>
    </div>
</body>
</html>
