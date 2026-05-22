<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadAlertNotification;
use Illuminate\Console\Command;

class NotifyStaleLeads extends Command
{
    protected $signature = 'alerts:notify-stale-leads';

    protected $description = 'Notifica leads ingresados hace mas de 24 horas sin contacto inicial.';

    public function handle(): int
    {
        $users = User::query()->get();
        $processed = 0;

        Lead::query()
            ->where('estado_pipeline', Lead::STAGE_INGRESADO)
            ->where('created_at', '<', now()->subHours(24))
            ->where('notificado', false)
            ->chunkById(100, function ($leads) use ($users, &$processed): void {
                foreach ($leads as $lead) {
                    foreach ($users as $user) {
                        $user->notify(new LeadAlertNotification($lead));
                    }

                    $lead->forceFill(['notificado' => true])->save();
                    $processed++;
                }
            });

        $this->info("Leads notificados: {$processed}");

        return self::SUCCESS;
    }
}
