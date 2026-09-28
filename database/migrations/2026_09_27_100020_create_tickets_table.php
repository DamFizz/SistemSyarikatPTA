<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code')->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained('ticket_categories')->restrictOnDelete();
            $table->string('title');
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('attachment')->nullable();
            $table->foreignId('assigned_technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['open', 'assigned', 'in_progress', 'waiting_user', 'resolved', 'closed'])->default('open');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
