<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('basic_salary', 10, 2);
            $table->decimal('total_allowance', 10, 2)->default(0);
            $table->decimal('total_bonus', 10, 2)->default(0);
            $table->decimal('total_ot_amount', 10, 2)->default(0);
            $table->decimal('gross_salary', 10, 2)->default(0);
            $table->decimal('total_deduction', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2)->default(0);
            $table->enum('status', ['draft', 'processing', 'approved', 'paid'])->default('draft');
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
