<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->index(['comercial_asignado_id', 'fecha_ingreso'], 'leads_commercial_ingreso_idx');
            $table->index(['comercial_asignado_id', 'estado_pipeline', 'fecha_ultimo_seguimiento'], 'leads_commercial_pipeline_followup_idx');
        });

        Schema::table('seguimientos', function (Blueprint $table): void {
            $table->index(['lead_id', 'estado', 'fecha_proxima_accion'], 'seguimientos_lead_estado_proxima_idx');
            $table->index(['comercial_id', 'estado', 'fecha_proxima_accion'], 'seguimientos_comercial_estado_proxima_idx');
            $table->index(['lead_id', 'fecha_contacto', 'created_at'], 'seguimientos_lead_contacto_created_idx');
        });

        Schema::table('lead_histories', function (Blueprint $table): void {
            $table->index(['lead_id', 'status_to', 'changed_at'], 'lead_histories_lead_status_changed_idx');
            $table->index(['lead_id', 'event_key', 'changed_at'], 'lead_histories_lead_event_changed_idx');
        });
    }

    public function down(): void
    {
        Schema::table('lead_histories', function (Blueprint $table): void {
            $table->dropIndex('lead_histories_lead_status_changed_idx');
            $table->dropIndex('lead_histories_lead_event_changed_idx');
        });

        Schema::table('seguimientos', function (Blueprint $table): void {
            $table->dropIndex('seguimientos_lead_estado_proxima_idx');
            $table->dropIndex('seguimientos_comercial_estado_proxima_idx');
            $table->dropIndex('seguimientos_lead_contacto_created_idx');
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex('leads_commercial_ingreso_idx');
            $table->dropIndex('leads_commercial_pipeline_followup_idx');
        });
    }
};
