<?php

namespace App\Services\Samu;

use App\Filament\Resources\Claims\ClaimResource;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Habilitations\HabilitationResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Pendientes\PendienteResource;
use App\Filament\Resources\SamuEvents\SamuEventResource;
use App\Models\SamuEvent;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SamuReviewExportService
{
    public function streamCurrentViewCsv(Builder $query, ?string $activeTab = null): StreamedResponse
    {
        $fileName = sprintf(
            'samu-review-%s-%s.csv',
            $activeTab ?: 'todos',
            now()->format('Ymd_His'),
        );

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');

            if (! $handle) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $this->headers(), ';');

            $exportQuery = (clone $query)
                ->with([
                    'lead:id,crm_id,nombre,apellido',
                    'client:id,name',
                    'claim:id,title',
                    'pendiente:id,type',
                    'habilitation:id,equipment,status',
                    'manualReviewer:id,name',
                ])
                ->orderBy('id');

            foreach ($exportQuery->lazyById(200, 'id') as $event) {
                /** @var SamuEvent $event */
                fputcsv($handle, $this->mapEvent($event), ';');
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function headers(): array
    {
        return [
            'ID',
            'External ID',
            'Evento',
            'Resumen',
            'Revision',
            'Catalogacion',
            'Override manual',
            'Procesado',
            'Interes',
            'Pipeline',
            'Siguiente paso',
            'Error',
            'Reviso manual',
            'Fecha revision manual',
            'Lead',
            'URL Lead',
            'Cliente',
            'URL Cliente',
            'Claim',
            'URL Claim',
            'Pendiente',
            'URL Pendiente',
            'Habilitacion',
            'URL Habilitacion',
            'URL Evento Samu',
            'Recibido',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function mapEvent(SamuEvent $event): array
    {
        $reviewStatus = $event->getReviewStatusMeta();

        return [
            (string) $event->id,
            (string) ($event->external_id ?? ''),
            (string) ($event->event_type ?? ''),
            (string) ($event->summary ?? ''),
            $reviewStatus['label'],
            SamuEvent::getClassificationLabel($event->classification),
            filled($event->manual_classification)
                ? SamuEvent::getClassificationLabel($event->manual_classification)
                : 'Auto',
            $event->processed ? 'Si' : 'No',
            (string) ($event->interest_level ? SamuEvent::getInterestLevelLabel($event->interest_level) : ''),
            (string) ($event->pipeline_stage ?? ''),
            (string) ($event->next_step ?? ''),
            (string) ($event->error_message ?? ''),
            (string) ($event->manualReviewer?->name ?? ''),
            (string) optional($event->manual_reviewed_at)->format('d/m/Y H:i'),
            $this->leadLabel($event),
            $event->lead_id ? url(LeadResource::getUrl('edit', ['record' => $event->lead_id])) : '',
            (string) ($event->client?->name ?? ''),
            $event->client_id ? url(ClientResource::getUrl('edit', ['record' => $event->client_id])) : '',
            (string) ($event->claim?->title ?? ''),
            $event->claim_id ? url(ClaimResource::getUrl('edit', ['record' => $event->claim_id])) : '',
            $this->pendienteLabel($event),
            $event->pendiente_id ? url(PendienteResource::getUrl('edit', ['record' => $event->pendiente_id])) : '',
            $this->habilitationLabel($event),
            $event->habilitation_id ? url(HabilitationResource::getUrl('edit', ['record' => $event->habilitation_id])) : '',
            url(SamuEventResource::getUrl('edit', ['record' => $event])),
            (string) optional($event->created_at)->format('d/m/Y H:i'),
        ];
    }

    protected function leadLabel(SamuEvent $event): string
    {
        if (! $event->lead) {
            return '';
        }

        return trim(implode(' | ', array_filter([
            $event->lead->crm_id,
            trim(($event->lead->nombre ?? '') . ' ' . ($event->lead->apellido ?? '')),
        ])));
    }

    protected function pendienteLabel(SamuEvent $event): string
    {
        if (! $event->pendiente) {
            return '';
        }

        return implode(' | ', array_filter([
            '#' . $event->pendiente_id,
            $event->pendiente->type,
        ]));
    }

    protected function habilitationLabel(SamuEvent $event): string
    {
        if (! $event->habilitation) {
            return '';
        }

        return implode(' | ', array_filter([
            '#' . $event->habilitation_id,
            $event->habilitation->equipment,
            $event->habilitation->status,
        ]));
    }
}
