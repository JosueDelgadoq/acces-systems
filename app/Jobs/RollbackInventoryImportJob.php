<?php

namespace App\Jobs;

use App\Models\InventoryImportBatch;
use App\Models\User;
use App\Services\InventoryImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RollbackInventoryImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $batchId,
        public ?int $userId = null,
    ) {
    }

    public function handle(InventoryImportService $service): void
    {
        $batch = InventoryImportBatch::query()->findOrFail($this->batchId);
        $user = $this->userId ? User::query()->find($this->userId) : null;

        $service->rollback($batch, $user);
    }
}
