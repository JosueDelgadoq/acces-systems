<?php

namespace App\Console\Commands;

use App\Services\Alerts\CommercialAlertService;
use App\Services\Alerts\PendingDueAlertService;
use Illuminate\Console\Command;

class ProcessAlerts extends Command
{
    protected $signature = 'alerts:process';

    protected $description = 'Procesa las alertas configuradas del sistema.';

    public function handle(
        PendingDueAlertService $pendingDueAlertService,
        CommercialAlertService $commercialAlertService,
    ): int
    {
        $pendingSummary = $pendingDueAlertService->process();
        $commercialSummary = $commercialAlertService->process();

        $this->info('Alertas procesadas.');
        $this->line('Pendientes evaluados: ' . $pendingSummary['processed']);
        $this->line('Mails enviados por pendientes: ' . $pendingSummary['mail']);
        $this->line('WhatsApp enviados por pendientes: ' . $pendingSummary['whatsapp']);
        $this->line('Notificaciones internas por pendientes: ' . $pendingSummary['database']);
        $this->line('Responsables comerciales con alertas: ' . $commercialSummary['affected']);
        $this->line('Mails comerciales enviados: ' . $commercialSummary['commercial_mail']);
        $this->line('Mails gerenciales enviados: ' . $commercialSummary['management_mail']);

        return self::SUCCESS;
    }
}
