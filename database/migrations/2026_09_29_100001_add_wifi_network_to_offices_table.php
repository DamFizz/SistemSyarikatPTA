<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The NFC tag no longer carries an ID the website reads. It now holds the office
     * WiFi credentials, and attendance is verified by checking the employee is on
     * that office network (its public IP address).
     */
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->string('wifi_ssid')->nullable()->after('allowed_radius_meters');
            $table->text('wifi_password')->nullable()->after('wifi_ssid');
            $table->string('wifi_security', 10)->default('WPA')->after('wifi_password');
            $table->text('allowed_ips')->nullable()->after('wifi_security');
            $table->boolean('network_check_enabled')->default(true)->after('allowed_ips');
            $table->dropColumn('nfc_tag_id');
        });
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->string('nfc_tag_id')->nullable()->after('allowed_radius_meters');
            $table->dropColumn(['wifi_ssid', 'wifi_password', 'wifi_security', 'allowed_ips', 'network_check_enabled']);
        });
    }
};
