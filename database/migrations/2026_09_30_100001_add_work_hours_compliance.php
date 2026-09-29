<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // auto = follows the salary threshold, enforced = always capped, exempt = never capped.
            $table->string('ot_cap_mode', 10)->default('auto');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('announcements_seen_at')->nullable();
        });

        Schema::create('rest_day_justifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('attendance')->nullOnDelete();
            $table->date('work_date');
            $table->unsignedSmallInteger('consecutive_days');
            $table->text('reason');
            $table->string('status', 15)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rest_day_justifications');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('announcements_seen_at');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('ot_cap_mode');
        });
    }
};
