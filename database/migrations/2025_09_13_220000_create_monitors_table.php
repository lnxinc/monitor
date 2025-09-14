<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->unsignedInteger('frequency_minutes')->default(5);
            $table->enum('status', ['up', 'down', 'unknown'])->default('unknown');
            $table->unsignedSmallInteger('last_status_code')->nullable();
            $table->unsignedInteger('last_response_time_ms')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->unsignedSmallInteger('consecutive_failures')->default(0);
            // SSL metadata (for https URLs)
            $table->timestamp('ssl_valid_from')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->string('ssl_issuer')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};

