<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadHistory;
use App\Models\Seguimiento;
use App\Models\User;
use Illuminate\Support\Carbon;

class LeadHistoryService
{
    protected const TRACKED_COLUMNS = [
        'estado_pipeline',
        'comercial_asignado_id',
        'resultado_final',
        'motivo_perdida',
        'fecha_cierre',
        'canal_origen',
        'producto_interes',
        'telefono',
        'email',
        'orientacion_dada',
        'tipo_orientacion',
        'fecha_orientacion',
        'area_responsable',
    ];

    public function recordCreated(Lead $lead): void
    {
        $this->record(
            $lead,
            eventKey: 'created',
            title: 'Lead ingresado',
            description: $this->buildCreatedDescription($lead),
            statusFrom: null,
            statusTo: $lead->estado_pipeline,
        );
    }

    public function recordUpdated(Lead $lead): void
    {
        if (! $lead->wasChanged(self::TRACKED_COLUMNS)) {
            return;
        }

        $title = $this->resolveUpdateTitle($lead);
        $description = $this->buildUpdatedDescription($lead);

        if (blank($title) || blank($description)) {
            return;
        }

        $statusChanged = $lead->wasChanged('estado_pipeline');

        $this->record(
            $lead,
            eventKey: $statusChanged ? 'pipeline_updated' : 'lead_updated',
            title: $title,
            description: $description,
            statusFrom: $statusChanged ? $lead->getOriginal('estado_pipeline') : $lead->estado_pipeline,
            statusTo: $lead->estado_pipeline,
        );
    }

    public function recordSeguimientoCreated(Seguimiento $seguimiento): void
    {
        $lead = $seguimiento->lead;

        if (! $lead) {
            return;
        }

        $this->record(
            $lead,
            eventKey: $seguimiento->estado === Seguimiento::STATUS_COMPLETADO ? 'follow_up_logged' : 'follow_up_created',
            title: $seguimiento->estado === Seguimiento::STATUS_COMPLETADO ? 'Seguimiento registrado' : 'Seguimiento programado',
            description: $this->buildSeguimientoDescription($seguimiento),
            statusFrom: $lead->estado_pipeline,
            statusTo: $lead->estado_pipeline,
            userId: $seguimiento->comercial_id ?: auth()->id(),
        );
    }

    public function recordSeguimientoUpdated(Seguimiento $seguimiento): void
    {
        if (! $seguimiento->wasChanged([
            'estado',
            'fecha_contacto',
            'medio_contacto',
            'resultado',
            'proxima_accion',
            'fecha_proxima_accion',
            'observaciones',
        ])) {
            return;
        }

        $lead = $seguimiento->lead;

        if (! $lead) {
            return;
        }

        $title = match (true) {
            $seguimiento->wasChanged('estado') && $seguimiento->estado === Seguimiento::STATUS_COMPLETADO => 'Seguimiento completado',
            $seguimiento->wasChanged('estado') && $seguimiento->estado === Seguimiento::STATUS_CANCELADO => 'Seguimiento cancelado',
            default => 'Seguimiento actualizado',
        };

        $this->record(
            $lead,
            eventKey: 'follow_up_updated',
            title: $title,
            description: $this->buildSeguimientoDescription($seguimiento),
            statusFrom: $lead->estado_pipeline,
            statusTo: $lead->estado_pipeline,
            userId: $seguimiento->comercial_id ?: auth()->id(),
        );
    }

    protected function record(
        Lead $lead,
        string $eventKey,
        string $title,
        ?string $description,
        ?string $statusFrom,
        ?string $statusTo,
        ?int $userId = null,
        array $meta = [],
    ): void {
        LeadHistory::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $userId ?? auth()->id() ?? $lead->comercial_asignado_id ?? $lead->created_by,
            'event_key' => $eventKey,
            'title' => $title,
            'description' => $description,
            'status_from' => $statusFrom,
            'status_to' => $statusTo,
            'meta' => $meta,
            'changed_at' => now(),
        ]);
    }

    protected function buildCreatedDescription(Lead $lead): string
    {
        $parts = [
            'Cliente: ' . trim($lead->cliente),
            'Canal: ' . (Lead::getCanalOrigenOptions()[$lead->canal_origen] ?? $lead->canal_origen),
            'Pipeline inicial: ' . Lead::getPipelineLabel($lead->estado_pipeline),
        ];

        if (filled($lead->comercialAsignado?->name)) {
            $parts[] = 'Comercial asignado: ' . $lead->comercialAsignado->name;
        }

        if (filled($lead->producto_interes)) {
            $parts[] = 'Interes: ' . $lead->producto_interes;
        }

        return implode('. ', array_filter($parts)) . '.';
    }

    protected function buildUpdatedDescription(Lead $lead): ?string
    {
        $changes = [];

        if ($lead->wasChanged('estado_pipeline')) {
            $changes[] = 'Pipeline: '
                . Lead::getPipelineLabel($lead->getOriginal('estado_pipeline'))
                . ' -> '
                . Lead::getPipelineLabel($lead->estado_pipeline);
        }

        if ($lead->wasChanged('comercial_asignado_id')) {
            $oldUser = $this->resolveUser($lead->getOriginal('comercial_asignado_id'));
            $newUser = $this->resolveUser($lead->comercial_asignado_id);

            $changes[] = match (true) {
                blank($lead->getOriginal('comercial_asignado_id')) && filled($lead->comercial_asignado_id) => 'Comercial asignado: ' . ($newUser?->name ?? 'Sin asignar'),
                filled($lead->getOriginal('comercial_asignado_id')) && blank($lead->comercial_asignado_id) => 'Se retiro la asignacion comercial de ' . ($oldUser?->name ?? 'usuario anterior'),
                default => 'Reasignado de ' . ($oldUser?->name ?? 'Sin asignar') . ' a ' . ($newUser?->name ?? 'Sin asignar'),
            };
        }

        if ($lead->wasChanged('resultado_final')) {
            $changes[] = 'Resultado: ' . $this->formatValue($lead->getOriginal('resultado_final'), Lead::getResultadoOptions()) . ' -> ' . $this->formatValue($lead->resultado_final, Lead::getResultadoOptions());
        }

        if ($lead->wasChanged('motivo_perdida')) {
            $changes[] = 'Motivo de perdida: ' . $this->formatValue($lead->motivo_perdida, Lead::getMotivoPerdidaOptions());
        }

        if ($lead->wasChanged('fecha_cierre')) {
            $changes[] = 'Fecha de cierre: ' . $this->formatDate($lead->getOriginal('fecha_cierre')) . ' -> ' . $this->formatDate($lead->fecha_cierre);
        }

        if ($lead->wasChanged('canal_origen')) {
            $changes[] = 'Canal de origen: ' . $this->formatValue($lead->getOriginal('canal_origen'), Lead::getCanalOrigenOptions()) . ' -> ' . $this->formatValue($lead->canal_origen, Lead::getCanalOrigenOptions());
        }

        if ($lead->wasChanged('producto_interes')) {
            $changes[] = 'Producto de interes: ' . $this->formatTextChange($lead->getOriginal('producto_interes'), $lead->producto_interes);
        }

        if ($lead->wasChanged('telefono')) {
            $changes[] = 'Telefono: ' . $this->formatTextChange($lead->getOriginal('telefono'), $lead->telefono);
        }

        if ($lead->wasChanged('email')) {
            $changes[] = 'Email: ' . $this->formatTextChange($lead->getOriginal('email'), $lead->email);
        }

        if ($lead->wasChanged('orientacion_dada')) {
            $changes[] = 'Orientacion comercial: ' . ($lead->orientacion_dada ? 'Realizada' : 'Pendiente');
        }

        if ($lead->wasChanged('tipo_orientacion')) {
            $changes[] = 'Tipo de orientacion: ' . $this->formatValue($lead->tipo_orientacion, Lead::getTipoOrientacionOptions());
        }

        if ($lead->wasChanged('fecha_orientacion')) {
            $changes[] = 'Fecha de orientacion: ' . $this->formatDate($lead->getOriginal('fecha_orientacion')) . ' -> ' . $this->formatDate($lead->fecha_orientacion);
        }

        if ($lead->wasChanged('area_responsable')) {
            $changes[] = 'Area responsable: ' . $this->formatValue($lead->getOriginal('area_responsable'), Lead::getAreaResponsableOptions()) . ' -> ' . $this->formatValue($lead->area_responsable, Lead::getAreaResponsableOptions());
        }

        return $changes !== [] ? implode('. ', $changes) . '.' : null;
    }

    protected function buildSeguimientoDescription(Seguimiento $seguimiento): string
    {
        $parts = [
            'Estado: ' . (Seguimiento::getStatusOptions()[$seguimiento->estado] ?? $seguimiento->estado),
        ];

        if ($seguimiento->fecha_contacto) {
            $parts[] = 'Fecha de contacto: ' . $this->formatDate($seguimiento->fecha_contacto);
        }

        if (filled($seguimiento->medio_contacto)) {
            $parts[] = 'Medio: ' . (Seguimiento::getMedioContactoOptions()[$seguimiento->medio_contacto] ?? $seguimiento->medio_contacto);
        }

        if (filled($seguimiento->resultado)) {
            $parts[] = 'Resultado: ' . trim((string) $seguimiento->resultado);
        }

        if (filled($seguimiento->proxima_accion)) {
            $parts[] = 'Proxima accion: ' . trim((string) $seguimiento->proxima_accion);
        }

        if ($seguimiento->fecha_proxima_accion) {
            $parts[] = 'Fecha proxima accion: ' . $this->formatDate($seguimiento->fecha_proxima_accion);
        }

        if (filled($seguimiento->observaciones)) {
            $parts[] = 'Observaciones: ' . trim((string) $seguimiento->observaciones);
        }

        return implode('. ', $parts) . '.';
    }

    protected function resolveUpdateTitle(Lead $lead): string
    {
        if ($lead->wasChanged('estado_pipeline')) {
            return match ($lead->estado_pipeline) {
                Lead::STAGE_VENTA_CERRADA => 'Lead cerrado como venta',
                Lead::STAGE_PERDIDO => 'Lead marcado como perdido',
                Lead::STAGE_POSTERGADO => 'Lead postergado',
                default => 'Pipeline actualizado',
            };
        }

        if ($lead->wasChanged('comercial_asignado_id')) {
            return 'Responsable comercial actualizado';
        }

        if ($lead->wasChanged(['orientacion_dada', 'tipo_orientacion', 'fecha_orientacion'])) {
            return 'Orientacion comercial actualizada';
        }

        if ($lead->wasChanged(['resultado_final', 'motivo_perdida', 'fecha_cierre'])) {
            return 'Condicion de cierre actualizada';
        }

        return 'Lead actualizado';
    }

    protected function resolveUser(mixed $userId): ?User
    {
        if (blank($userId)) {
            return null;
        }

        return User::query()
            ->select(['id', 'name'])
            ->find($userId);
    }

    protected function formatValue(mixed $value, array $labels = []): string
    {
        if (blank($value)) {
            return 'Sin definir';
        }

        return $labels[$value] ?? (string) $value;
    }

    protected function formatDate(mixed $value): string
    {
        if (blank($value)) {
            return 'Sin fecha';
        }

        return Carbon::parse($value)->format('d/m/Y');
    }

    protected function formatTextChange(mixed $from, mixed $to): string
    {
        return (filled($from) ? trim((string) $from) : 'Sin dato')
            . ' -> '
            . (filled($to) ? trim((string) $to) : 'Sin dato');
    }
}
