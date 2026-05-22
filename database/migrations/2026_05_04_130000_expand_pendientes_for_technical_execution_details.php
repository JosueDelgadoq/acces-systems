<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendientes', function (Blueprint $table) {
            $table->text('initial_diagnosis')->nullable()->after('description');
            $table->text('possible_spare_parts')->nullable()->after('initial_diagnosis');
            $table->string('client_availability')->nullable()->after('suggested_priority');
            $table->string('estimated_time')->nullable()->after('assigned_at');
            $table->boolean('post_visit_acknowledged')->default(false)->after('technician_signature_notes');
            $table->string('post_visit_signature_name')->nullable()->after('post_visit_acknowledged');
            $table->timestamp('post_visit_signature_at')->nullable()->after('post_visit_signature_name');
            $table->text('post_visit_signature_notes')->nullable()->after('post_visit_signature_at');
        });
    }

    public function down(): void
    {
        Schema::table('pendientes', function (Blueprint $table) {
            $table->dropColumn([
                'initial_diagnosis',
                'possible_spare_parts',
                'client_availability',
                'estimated_time',
                'post_visit_acknowledged',
                'post_visit_signature_name',
                'post_visit_signature_at',
                'post_visit_signature_notes',
            ]);
        });
    }
};
