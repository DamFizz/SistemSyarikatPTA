<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->string('nfc_tag_id')->nullable()->after('allowed_radius_meters');
        });
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn('nfc_tag_id');
        });
    }
};
