<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->string('verification_method', 20)->nullable()->change();

            $table->unsignedInteger('clock_in_accuracy_meters')->nullable()->after('clock_in_distance_meters');
            $table->unsignedInteger('clock_out_distance_meters')->nullable()->after('clock_out_lng');
            $table->unsignedInteger('clock_out_accuracy_meters')->nullable()->after('clock_out_distance_meters');
            $table->string('selfie_hash', 64)->nullable()->index()->after('selfie_path');
            $table->string('clock_out_selfie_path')->nullable()->after('selfie_hash');
            $table->string('clock_out_selfie_hash', 64)->nullable()->index()->after('clock_out_selfie_path');
            $table->string('clock_out_ip_address', 45)->nullable()->after('ip_address');
            $table->string('device_hash', 64)->nullable()->index()->after('device_info');
            $table->json('flag_reasons')->nullable()->after('is_flagged');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('registered_device_hash', 64)->nullable();
            $table->timestamp('device_registered_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropIndex(['selfie_hash']);
            $table->dropIndex(['clock_out_selfie_hash']);
            $table->dropIndex(['device_hash']);
            $table->dropColumn([
                'clock_in_accuracy_meters', 'clock_out_distance_meters', 'clock_out_accuracy_meters',
                'selfie_hash', 'clock_out_selfie_path', 'clock_out_selfie_hash', 'clock_out_ip_address',
                'device_hash', 'flag_reasons',
            ]);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['registered_device_hash', 'device_registered_at']);
        });
    }
};
