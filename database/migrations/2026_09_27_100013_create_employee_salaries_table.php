<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('basic_salary', 10, 2);
            $table->decimal('allowance', 10, 2)->default(0);
            $table->decimal('epf_rate', 5, 2)->default(11.00);
            $table->decimal('socso_rate', 5, 2)->default(0.50);
            $table->decimal('eis_rate', 5, 2)->default(0.20);
            $table->date('effective_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
    }
};
