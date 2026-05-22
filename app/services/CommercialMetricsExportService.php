<?php

namespace App\Services;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class CommercialMetricsExportService
{
    public function __construct(
        protected CommercialMetricsService $metrics,
    ) {
    }

    public function downloadXlsx(User $viewer, Carbon $from, Carbon $until, ?int $commercialId = null)
    {
        $report = $this->metrics->build($viewer, $from, $until, $commercialId);
        $filename = 'metricas_comerciales_' . $from->format('Ymd') . '_' . $until->format('Ymd') . '.xlsx';
        $tempPath = storage_path('app/tmp/' . Str::uuid() . '_' . $filename);

        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        $writer = new Writer();
        $writer->openToFile($tempPath);
        $writer->setCreator('ERP Access Systems');

        $this->writeSummarySheet($writer, $report);
        $this->writeFunnelSheet($writer, $report);
        $this->writeTeamSheet($writer, $report);
        $this->writeSourcesSheet($writer, $report);
        $this->writeLossReasonsSheet($writer, $report);
        $this->writeGoalsSheet($writer, $report);

        $writer->close();

        return response()->download(
            $tempPath,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }

    public function downloadPdf(User $viewer, Carbon $from, Carbon $until, ?int $commercialId = null)
    {
        $report = $this->metrics->build($viewer, $from, $until, $commercialId);

        $pdf = Pdf::loadView('reports.commercial-metrics', [
            'report' => $report,
        ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'metricas_comerciales_' . $from->format('Ymd') . '_' . $until->format('Ymd') . '.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    protected function writeSummarySheet(Writer $writer, array $report): void
    {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Resumen');

        $rows = [
            ['Metricas comerciales', ''],
            ['Periodo', $report['hero']['period_label']],
            ['Cobertura', $report['hero']['scope_label']],
            [],
            ['Indicador', 'Valor', 'Detalle'],
        ];

        foreach ($report['overview_cards'] as $card) {
            $rows[] = [$card['label'], $card['value'], $card['note']];
        }

        $rows[] = [];
        $rows[] = ['Insight', 'Detalle'];

        foreach ($report['insights'] as $insight) {
            $rows[] = [$insight['title'], $insight['description']];
        }

        $writer->addRows(array_map(fn (array $row): Row => Row::fromValues($row), $rows));
    }

    protected function writeFunnelSheet(Writer $writer, array $report): void
    {
        $writer->addNewSheetAndMakeItCurrent()->setName('Embudo');

        $rows = [
            ['Etapa', 'Leads', 'Participacion', 'Avance', 'Drop off'],
        ];

        foreach ($report['funnel'] as $stage) {
            $rows[] = [
                $stage['label'],
                $stage['count'],
                $stage['share'],
                $stage['step_conversion'],
                $stage['drop_off'],
            ];
        }

        $writer->addRows(array_map(fn (array $row): Row => Row::fromValues($row), $rows));
    }

    protected function writeTeamSheet(Writer $writer, array $report): void
    {
        $writer->addNewSheetAndMakeItCurrent()->setName('Equipo');

        $rows = [
            ['Comercial', 'Leads', 'Contactados', 'Cotizados', 'Ganados', 'Perdidos', 'Sin ritmo', 'Conversion', 'Ingresos'],
        ];

        foreach ($report['team_performance'] as $row) {
            $rows[] = [
                $row['name'],
                $row['leads'],
                $row['contacted'],
                $row['quoted'],
                $row['won'],
                $row['lost'],
                $row['stale'],
                $row['conversion'],
                $row['revenue'],
            ];
        }

        $writer->addRows(array_map(fn (array $row): Row => Row::fromValues($row), $rows));
    }

    protected function writeSourcesSheet(Writer $writer, array $report): void
    {
        $writer->addNewSheetAndMakeItCurrent()->setName('Canales');

        $rows = [
            ['Canal', 'Leads', 'Contacto', 'Ganados', 'Conversion', 'Ingresos'],
        ];

        foreach ($report['source_breakdown'] as $row) {
            $rows[] = [
                $row['channel'],
                $row['leads'],
                $row['contact_rate'],
                $row['won'],
                $row['conversion'],
                $row['revenue'],
            ];
        }

        $writer->addRows(array_map(fn (array $row): Row => Row::fromValues($row), $rows));
    }

    protected function writeLossReasonsSheet(Writer $writer, array $report): void
    {
        $writer->addNewSheetAndMakeItCurrent()->setName('Perdidas');

        $rows = [
            ['Motivo', 'Casos', 'Participacion'],
        ];

        foreach ($report['loss_reasons'] as $row) {
            $rows[] = [
                $row['reason'],
                $row['count'],
                $row['share'],
            ];
        }

        $writer->addRows(array_map(fn (array $row): Row => Row::fromValues($row), $rows));
    }

    protected function writeGoalsSheet(Writer $writer, array $report): void
    {
        $writer->addNewSheetAndMakeItCurrent()->setName('Objetivos');

        $rows = [
            ['Mes objetivo', $report['goal_scoreboard']['month_label']],
            [],
            ['Indicador', 'Valor', 'Detalle'],
        ];

        foreach ($report['goal_scoreboard']['summary_cards'] as $card) {
            $rows[] = [$card['label'], $card['value'], $card['note']];
        }

        $rows[] = [];
        $rows[] = [
            'Comercial',
            'Meta leads',
            'Real leads',
            'Desvio leads',
            'Meta contactos',
            'Real contactos',
            'Desvio contactos',
            'Meta ventas',
            'Real ventas',
            'Desvio ventas',
            'Meta facturacion',
            'Real facturacion',
            'Desvio facturacion',
            'Cumplimiento',
            'Estado',
        ];

        foreach ($report['goal_scoreboard']['rows'] as $row) {
            $rows[] = [
                $row['name'],
                $row['goal_leads'],
                $row['actual_leads'],
                $row['lead_delta'],
                $row['goal_contacts'],
                $row['actual_contacts'],
                $row['contact_delta'],
                $row['goal_sales'],
                $row['actual_sales'],
                $row['sales_delta'],
                $row['goal_revenue'],
                $row['actual_revenue'],
                $row['revenue_delta'],
                $row['attainment'],
                $row['status_label'],
            ];
        }

        $writer->addRows(array_map(fn (array $row): Row => Row::fromValues($row), $rows));
    }
}
