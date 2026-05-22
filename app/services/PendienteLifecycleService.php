<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Pendiente;

class PendienteLifecycleService
{
    /**
     * @var array<string, bool>
     */
    protected array $capabilities = [];

    public function __construct(
        protected PendienteSchemaService $schema,
    ) {
    }

    public function prepareForSave(Pendiente $pendiente): void
    {
        $this->capabilities = [
            'workflow_stage' => $this->schema->hasColumn('workflow_stage'),
            'service_address' => $this->schema->hasColumn('service_address'),
            'suggested_priority' => $this->schema->hasColumn('suggested_priority'),
            'required_tools' => $this->schema->hasColumn('required_tools'),
            'assigned_at' => $this->schema->hasColumn('assigned_at'),
            'technician_signature' => $this->schema->hasColumns([
                'technician_acknowledged',
                'technician_signature_name',
                'technician_signature_at',
            ]),
            'post_visit_signature' => $this->schema->hasColumns([
                'post_visit_acknowledged',
                'post_visit_signature_name',
                'post_visit_signature_at',
            ]),
        ];

        if ($this->capabilities['workflow_stage']) {
            $this->applyTechnicalBoardDefaults($pendiente);
        }

        $this->syncCompletionTimestamps($pendiente);
    }

    protected function applyTechnicalBoardDefaults(Pendiente $pendiente): void
    {
        if ($this->capabilities['service_address']) {
            $pendiente->service_address ??= $this->resolveClientAddress($pendiente);
        }

        if ($this->capabilities['suggested_priority']) {
            $pendiente->suggested_priority ??= $pendiente->priority ?? 'media';
        }

        $pendiente->priority ??= $pendiente->suggested_priority ?? 'media';

        if ($this->capabilities['required_tools']) {
            $pendiente->required_tools = Pendiente::normalizeRequiredTools(
                $pendiente->required_tools,
                $pendiente->type,
            );
        }

        if ($this->capabilities['assigned_at'] && $pendiente->isDirty('user_id') && filled($pendiente->user_id)) {
            $pendiente->assigned_at ??= now();
        }

        if (
            $this->capabilities['technician_signature']
            && ($pendiente->technician_acknowledged || filled($pendiente->technician_signature_name))
        ) {
            $pendiente->technician_acknowledged = true;
            $pendiente->technician_signature_at ??= now();
        }

        if (
            $this->capabilities['post_visit_signature']
            && ($pendiente->post_visit_acknowledged || filled($pendiente->post_visit_signature_name))
        ) {
            $pendiente->post_visit_acknowledged = true;
            $pendiente->post_visit_signature_at ??= now();
        }

        if ($this->capabilities['workflow_stage']) {
            $pendiente->workflow_stage = Pendiente::resolveWorkflowStage($pendiente);
        }
    }

    protected function syncCompletionTimestamps(Pendiente $pendiente): void
    {
        if ($pendiente->status === Pendiente::STATUS_COMPLETED) {
            $pendiente->completed_at ??= now();
            $pendiente->performed_at ??= now();

            return;
        }

        $pendiente->completed_at = null;
    }

    protected function resolveClientAddress(Pendiente $pendiente): ?string
    {
        if (filled($pendiente->service_address)) {
            return $pendiente->service_address;
        }

        $client = $pendiente->relationLoaded('client')
            ? $pendiente->getRelation('client')
            : $this->findClient($pendiente->client_id);

        return $client?->full_address;
    }

    protected function findClient(?int $clientId): ?Client
    {
        if (blank($clientId)) {
            return null;
        }

        return Client::query()
            ->select(['id', 'address', 'city', 'state', 'postal_code'])
            ->find($clientId);
    }
}
