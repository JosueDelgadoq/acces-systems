<?php

namespace App\Console\Commands;

use App\Services\CommercialDemoDataService;
use Illuminate\Console\Command;
use RuntimeException;

class SeedCommercialDemoData extends Command
{
    protected $signature = 'demo:seed-commercial-metrics
        {--count=18 : Cantidad de leads de prueba a crear}
        {--append : Permite agregar otra tanda aunque ya existan leads de prueba}';

    protected $description = 'Genera leads comerciales de prueba para poblar metricas sin tocar la cartera real.';

    public function handle(CommercialDemoDataService $service): int
    {
        try {
            $summary = $service->seed(
                count: (int) $this->option('count'),
                append: (bool) $this->option('append'),
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Carga comercial de prueba completada.');
        $this->line('Leads creados: ' . $summary['created_leads']);
        $this->line('Seguimientos creados: ' . $summary['created_seguimientos']);
        $this->line('Presupuestos creados: ' . $summary['created_presupuestos']);
        $this->line('Ventas creadas: ' . $summary['created_ventas']);
        $this->line('Historiales creados: ' . $summary['created_histories']);
        $this->line('Comerciales involucrados: ' . implode(', ', $summary['commercials']));

        if ($summary['stage_breakdown'] !== []) {
            $this->newLine();
            $this->info('Distribucion por etapa:');

            foreach ($summary['stage_breakdown'] as $stage => $total) {
                $this->line("- {$stage}: {$total}");
            }
        }

        return self::SUCCESS;
    }
}
