<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_code')->unique();
            $table->string('full_name');
            $table->string('ic_number')->unique();
            $table->string('phone')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('dob')->nullable();
            $table->text('address')->nullable();
            $table->string('profile_photo')->nullable();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('office_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('position');
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])->default('full_time');
            $table->enum('employment_status', ['active', 'probation', 'resigned', 'terminated', 'suspended'])->default('probation');
            $table->date('join_date');
            $table->timestamps();

            $table->foreign('manager_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
