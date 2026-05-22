<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Metricas comerciales</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; margin: 24px; }
        h1, h2 { margin: 0 0 8px; }
        p { margin: 0 0 8px; color: #475569; }
        .hero { margin-bottom: 20px; padding: 18px; border: 1px solid #cbd5e1; border-radius: 16px; background: #f8fbff; }
        .chips { display: table; width: 100%; margin-top: 12px; }
        .chip { display: inline-block; width: 23%; margin-right: 1.5%; vertical-align: top; padding: 10px; border: 1px solid #dbeafe; border-radius: 12px; background: #ffffff; }
        .chip:last-child { margin-right: 0; }
        .chip span { display: block; font-size: 10px; text-transform: uppercase; color: #64748b; margin-bottom: 6px; }
        .chip strong { font-size: 18px; color: #0f172a; }
        .stats { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .stats td { width: 33.33%; padding: 12px; border: 1px solid #e2e8f0; vertical-align: top; }
        .stats strong { display: block; font-size: 18px; margin-top: 4px; }
        .section { margin-top: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #eff6ff; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #334155; }
        .insight { margin-top: 10px; padding: 10px 12px; border-left: 4px solid #2563eb; background: #f8fafc; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    <section class="hero">
        <h1>Metricas comerciales</h1>
        <p>Periodo: {{ $report['hero']['period_label'] }}</p>
        <p>Cobertura: {{ $report['hero']['scope_label'] }}</p>

        <div class="chips">
            @foreach ($report['hero']['chips'] as $chip)
                <div class="chip">
                    <span>{{ $chip['label'] }}</span>
                    <strong>{{ $chip['value'] }}</strong>
                </div>
            @endforeach
        </div>
    </section>

    <table class="stats">
        <tr>
            @foreach (array_chunk($report['overview_cards'], 3) as $chunk)
                @foreach ($chunk as $card)
                    <td>
                        <span class="muted">{{ $card['label'] }}</span>
                        <strong>{{ $card['value'] }}</strong>
                        <p>{{ $card['note'] }}</p>
                    </td>
                @endforeach
            @endforeach
        </tr>
    </table>

    <section class="section">
        <h2>Insights ejecutivos</h2>
        @foreach ($report['insights'] as $insight)
            <div class="insight">
                <strong>{{ $insight['title'] }}</strong>
                <p>{{ $insight['description'] }}</p>
            </div>
        @endforeach
    </section>

    <section class="section">
        <h2>Embudo</h2>
        <table>
            <thead>
                <tr>
                    <th>Etapa</th>
                    <th>Leads</th>
                    <th>Participacion</th>
                    <th>Avance</th>
                    <th>Drop off</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['funnel'] as $stage)
                    <tr>
                        <td>{{ $stage['label'] }}</td>
                        <td>{{ $stage['count'] }}</td>
                        <td>{{ $stage['share'] }}</td>
                        <td>{{ $stage['step_conversion'] }}</td>
                        <td>{{ $stage['drop_off'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Desempeno por vendedora</h2>
        <table>
            <thead>
                <tr>
                    <th>Comercial</th>
                    <th>Leads</th>
                    <th>Contactados</th>
                    <th>Cotizados</th>
                    <th>Ganados</th>
                    <th>Perdidos</th>
                    <th>Sin ritmo</th>
                    <th>Conversion</th>
                    <th>Ingresos</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['team_performance'] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['leads'] }}</td>
                        <td>{{ $row['contacted'] }}</td>
                        <td>{{ $row['quoted'] }}</td>
                        <td>{{ $row['won'] }}</td>
                        <td>{{ $row['lost'] }}</td>
                        <td>{{ $row['stale'] }}</td>
                        <td>{{ $row['conversion'] }}</td>
                        <td>{{ $row['revenue'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Canales y perdidas</h2>
        <table>
            <thead>
                <tr>
                    <th>Canal</th>
                    <th>Leads</th>
                    <th>Contacto</th>
                    <th>Ganados</th>
                    <th>Conversion</th>
                    <th>Ingresos</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['source_breakdown'] as $row)
                    <tr>
                        <td>{{ $row['channel'] }}</td>
                        <td>{{ $row['leads'] }}</td>
                        <td>{{ $row['contact_rate'] }}</td>
                        <td>{{ $row['won'] }}</td>
                        <td>{{ $row['conversion'] }}</td>
                        <td>{{ $row['revenue'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table style="margin-top: 14px;">
            <thead>
                <tr>
                    <th>Motivo de perdida</th>
                    <th>Casos</th>
                    <th>Participacion</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['loss_reasons'] as $row)
                    <tr>
                        <td>{{ $row['reason'] }}</td>
                        <td>{{ $row['count'] }}</td>
                        <td>{{ $row['share'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">No hay perdidas en el periodo.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Objetivos del mes {{ $report['goal_scoreboard']['month_label'] }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Comercial</th>
                    <th>Meta leads</th>
                    <th>Real leads</th>
                    <th>Desvio leads</th>
                    <th>Meta ventas</th>
                    <th>Real ventas</th>
                    <th>Desvio ventas</th>
                    <th>Meta facturacion</th>
                    <th>Real facturacion</th>
                    <th>Desvio facturacion</th>
                    <th>Cumplimiento</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['goal_scoreboard']['rows'] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['goal_leads'] }}</td>
                        <td>{{ $row['actual_leads'] }}</td>
                        <td>{{ $row['lead_delta'] }}</td>
                        <td>{{ $row['goal_sales'] }}</td>
                        <td>{{ $row['actual_sales'] }}</td>
                        <td>{{ $row['sales_delta'] }}</td>
                        <td>{{ $row['goal_revenue'] }}</td>
                        <td>{{ $row['actual_revenue'] }}</td>
                        <td>{{ $row['revenue_delta'] }}</td>
                        <td>{{ $row['attainment'] }}</td>
                        <td>{{ $row['status_label'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12">No hay objetivos cargados para este mes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</body>
</html>
