<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->unsignedTinyInteger('battery_level')->nullable();
            $table->boolean('is_moving')->default(false);
            $table->timestamp('tracked_at');
            $table->timestamps();

            $table->index(['user_id', 'tracked_at']);
            $table->index(['tracked_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('last_lat', 10, 7)->nullable()->after('remember_token');
            $table->decimal('last_lng', 10, 7)->nullable()->after('last_lat');
            $table->timestamp('last_seen_at')->nullable()->after('last_lng');
            $table->boolean('tracking_enabled')->default(false)->after('last_seen_at');
            $table->string('technician_status')->default('offline')->after('tracking_enabled');

            $table->index(['tracking_enabled', 'technician_status']);
            $table->index(['last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['tracking_enabled', 'technician_status']);
            $table->dropIndex(['last_seen_at']);
            $table->dropColumn([
                'last_lat',
                'last_lng',
                'last_seen_at',
                'tracking_enabled',
                'technician_status',
            ]);
        });

        Schema::dropIfExists('technician_locations');
    }
};
