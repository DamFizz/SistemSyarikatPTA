<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->date('attendance_date');
            $table->dateTime('clock_in_time')->nullable();
            $table->dateTime('clock_out_time')->nullable();
            $table->decimal('clock_in_lat', 10, 7)->nullable();
            $table->decimal('clock_in_lng', 10, 7)->nullable();
            $table->unsignedInteger('clock_in_distance_meters')->nullable();
            $table->decimal('clock_out_lat', 10, 7)->nullable();
            $table->decimal('clock_out_lng', 10, 7)->nullable();
            $table->string('selfie_path')->nullable();
            $table->foreignId('qr_token_id')->nullable()->constrained('attendance_qr_tokens')->nullOnDelete();
            $table->string('device_info')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->unsignedInteger('working_minutes')->nullable();
            $table->enum('status', ['present', 'late', 'absent', 'half_day', 'on_leave', 'public_holiday', 'work_from_home'])->default('present');
            $table->boolean('is_flagged')->default(false);
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
