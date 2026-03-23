<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('crm_id')->unique();
            $table->date('fecha_ingreso');
            $table->time('hora_ingreso');
            $table->foreignId('comercial_asignado_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('canal_origen', ['whatsapp', 'redes_sociales', 'mail', 'telefono'])->default('whatsapp');
            // DATOS CLIENTE
            $table->string('nombre');
            $table->string('apellido');
            $table->string('telefono');
            $table->string('email')->nullable()->unique();
            $table->string('localidad')->nullable();
            $table->string('provincia')->nullable();
            $table->string('zona_comercial')->nullable();
            // CLASIFICACION CLIENTE
            $table->enum('tipo_cliente', ['Residencial', 'Público', 'Empresa', 'Constructor'])->nullable();
            $table->enum('subtipo_publico', ['Universidad', 'Municipalidad', 'Banco', 'Hospital', 'Otro'])->nullable();
            // INTERES INICIAL
            $table->string('producto_interes')->nullable();
            $table->enum('tipo_instalacion', ['Recta', 'Curva', 'Exterior', 'Piscina', 'Vertical'])->nullable();
            $table->text('documentacion_cliente')->nullable();
            $table->boolean('cliente_envio_fotos')->default(false);
            $table->boolean('cliente_envio_planos')->default(false);
            $table->boolean('requiere_relevamiento_pago')->default(false);
            // ORIENTACION DE COSTOS
            $table->boolean('orientacion_dada')->default(false);
            $table->enum('tipo_orientacion', [
                'Verbal telefónica',
                'Audio WhatsApp',
                'Texto WhatsApp',
                'Estimado estructurado WhatsApp',
                'PDF enviado por WhatsApp',
                'PDF enviado por Mail'
            ])->nullable();
            $table->date('fecha_orientacion')->nullable();
            // PIPELINE COMERCIAL
            $table->enum('estado_pipeline', [
                'Ingresado',
                'Contactado', 
                'Orientacion dada',
                'Cotizacion enviada',
                'Presupuesto definitivo enviado',
                'Venta cerrada',
                'Perdido',
                'Postergado'
            ])->default('Ingresado');
            // RESULTADO FINAL
            $table->enum('resultado_final', ['Abierto', 'Vendido', 'Perdido'])->nullable();
            $table->enum('motivo_perdida', [
                'Precio',
                'Forma de pago',
                'Tiempo entrega', 
                'Competencia',
                'Calidad percibida',
                'Falta decisión',
                'Otro'
            ])->nullable();
            $table->date('fecha_cierre')->nullable();
            $table->enum('area_responsable', ['Comercial Venta', 'Postventa'])->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->index(['estado_pipeline', 'comercial_asignado_id']);
            $table->index('fecha_ingreso');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};

