<?php

namespace App\Observers;

use App\Models\Pendiente;
use App\Services\Alerts\PendingLifecycleNotificationService;
use App\Services\PendienteHistoryService;
use App\Services\PendienteLifecycleService;
use App\Services\ServiceVisitAssignmentService;

class PendienteObserver
{
    public function __construct(
        protected PendienteLifecycleService $lifecycle,
        protected PendienteHistoryService $history,
        protected PendingLifecycleNotificationService $notifications,
        protected ServiceVisitAssignmentService $serviceVisits,
    ) {
    }

    public function saving(Pendiente $pendiente): void
    {
        $this->lifecycle->prepareForSave($pendiente);
    }

    public function created(Pendiente $pendiente): void
    {
        $this->history->recordCreated($pendiente);
        $this->notifications->handleCreated($pendiente);
        $this->serviceVisits->syncFromPendiente($pendiente);
    }

    public function updated(Pendiente $pendiente): void
    {
        $this->history->recordUpdated($pendiente);
        $this->notifications->handleUpdated($pendiente);
        $this->serviceVisits->syncFromPendiente($pendiente);
    }
}
