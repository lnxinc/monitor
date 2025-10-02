<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_windows', function (Blueprint $table) {
            $table->id();
            $table->enum('scope', ['global', 'organization', 'device', 'monitor']);
            $table->unsignedBigInteger('ref_id')->nullable(); // organization_id, device_id, or monitor_id depending on scope
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['scope', 'ref_id']);
            $table->index(['start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_windows');
    }
};

