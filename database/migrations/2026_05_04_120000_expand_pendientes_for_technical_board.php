<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendientes', function (Blueprint $table) {
            $table->string('service_address')->nullable()->after('client_id');
            $table->string('neighborhood')->nullable()->after('service_address');
            $table->string('operational_zone')->nullable()->after('neighborhood');
            $table->string('workflow_stage')->default('postventa')->after('type');
            $table->string('suggested_priority')->nullable()->after('workflow_stage');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->after('reviewed_by_user_id')->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable()->after('notes');
            $table->timestamp('assigned_at')->nullable()->after('due_date');
            $table->string('service_order_number')->nullable()->after('remito');
            $table->text('work_order')->nullable()->after('service_order_number');
            $table->text('required_tools')->nullable()->after('work_order');
            $table->boolean('technician_acknowledged')->default(false)->after('required_tools');
            $table->string('technician_signature_name')->nullable()->after('technician_acknowledged');
            $table->timestamp('technician_signature_at')->nullable()->after('technician_signature_name');
            $table->text('technician_signature_notes')->nullable()->after('technician_signature_at');
            $table->text('control_summary')->nullable()->after('technician_signature_notes');
        });
    }

    public function down(): void
    {
        Schema::table('pendientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_by_user_id');
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropColumn([
                'service_address',
                'neighborhood',
                'operational_zone',
                'workflow_stage',
                'suggested_priority',
                'review_notes',
                'assigned_at',
                'service_order_number',
                'work_order',
                'required_tools',
                'technician_acknowledged',
                'technician_signature_name',
                'technician_signature_at',
                'technician_signature_notes',
                'control_summary',
            ]);
        });
    }
};
