<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Selfies live in the database so they survive redeploys on hosts with an
     * ephemeral filesystem (e.g. Railway). They are purged after their month ends.
     */
    public function up(): void
    {
        Schema::create('attendance_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendance')->cascadeOnDelete();
            $table->string('type', 3);
            $table->string('mime', 30);
            $table->unsignedInteger('size');
            $table->longText('data'); // base64 — portable across MySQL and SQLite
            $table->timestamps();

            $table->unique(['attendance_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_photos');
    }
};
