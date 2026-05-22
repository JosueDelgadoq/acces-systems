<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Pendientes</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { text-align: center; margin-bottom: 5px; }
        .subtitle { text-align: center; font-size: 10px; margin-bottom: 20px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px; border: 1px solid #ddd; }
        th { background: #f3f3f3; }

        .badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
        }

        .pending { background: #ffeeba; }
        .in_progress { background: #bee5eb; }
        .completed { background: #c3e6cb; }
        .cancelled { background: #e2e3e5; }

        .summary {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<h1>Reporte de Pendientes</h1>
<div class="subtitle">Generado: {{ now()->format('d/m/Y H:i') }}</div>

<div class="summary">
    <strong>Total:</strong> {{ $total }} |
    <strong>Activos:</strong> {{ $active }} |
    <strong>Cerrados:</strong> {{ $completed }}
</div>

<table>
    <thead>
        <tr>
            <th>Cliente</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Técnico</th>
            <th>Fecha</th>
            <th>Realizado</th>
            <th>Remito</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($pendientes as $p)
            <tr>
                <td>{{ $p->client?->name }}</td>
                <td>{{ ucfirst($p->type) }}</td>
                <td>
                    <span class="badge {{ $p->status }}">
                        {{ \App\Models\Pendiente::getStatusLabel($p->status) }}
                    </span>
                </td>
                <td>{{ $p->user?->name }}</td>
                <td>{{ optional($p->created_at)->format('d/m/Y') }}</td>
                <td>{{ optional($p->performed_at)->format('d/m/Y') }}</td>
                <td>{{ $p->remito }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>