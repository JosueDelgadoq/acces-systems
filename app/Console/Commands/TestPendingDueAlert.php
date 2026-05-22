<?php

namespace App\Console\Commands;

use App\Models\Pendiente;
use App\Services\Alerts\PendingDueAlertService;
use Illuminate\Console\Command;

class TestPendingDueAlert extends Command
{
    protected $signature = 'alerts:test-pending-due
        {email : Mail de destino para la prueba}
        {--phone= : Telefono de destino para probar WhatsApp}
        {--pendiente= : ID de pendiente a usar en la prueba}';

    protected $description = 'Envia una prueba de alerta de pendiente por mail y opcionalmente WhatsApp.';

    public function handle(PendingDueAlertService $pendingDueAlertService): int
    {
        $pendiente = $this->resolvePendiente();

        if (! $pendiente) {
            $this->error('No se encontro un pendiente activo para enviar la prueba.');

            return self::FAILURE;
        }

        $result = $pendingDueAlertService->sendTest(
            $pendiente,
            (string) $this->argument('email'),
            $this->option('phone') ? (string) $this->option('phone') : null,
        );

        $this->info("Prueba generada para pendiente #{$pendiente->id}.");
        $this->line('Mail(s): ' . $result['mail']);
        $this->line('WhatsApp: ' . $result['whatsapp']);

        if (config('mail.default') === 'log') {
            $this->warn('MAIL_MAILER=log: el mail se registro en el log, no se envio a un proveedor real.');
        }

        return self::SUCCESS;
    }

    protected function resolvePendiente(): ?Pendiente
    {
        $pendienteId = $this->option('pendiente');

        if ($pendienteId) {
            return Pendiente::query()->find($pendienteId);
        }

        return Pendiente::query()
            ->whereIn('status', [Pendiente::STATUS_PENDING, Pendiente::STATUS_IN_PROGRESS])
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->first();
    }
}
