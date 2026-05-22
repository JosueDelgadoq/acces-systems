<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('samu_events', function (Blueprint $table): void {
            $table->string('manual_classification')->nullable()->after('classification');
            $table->text('manual_review_notes')->nullable()->after('manual_classification');
            $table->foreignId('manual_reviewed_by')->nullable()->after('manual_review_notes')->index();
            $table->timestamp('manual_reviewed_at')->nullable()->after('manual_reviewed_by');
        });

        Schema::table('samu_events', function (Blueprint $table): void {
            $table->foreign('manual_reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('samu_events', function (Blueprint $table): void {
            $table->dropForeign(['manual_reviewed_by']);
            $table->dropColumn([
                'manual_classification',
                'manual_review_notes',
                'manual_reviewed_by',
                'manual_reviewed_at',
            ]);
        });
    }
};
