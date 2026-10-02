<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uploaded attachments (leave MCs, overtime evidence, ticket screenshots, announcement files)
 * live in the database: the hosting platform's disk is wiped on every deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stored_files', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->longText('data');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stored_files');
    }
};
