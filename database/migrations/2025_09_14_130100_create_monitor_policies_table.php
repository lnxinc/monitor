<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained('monitors')->onDelete('cascade');
            $table->unsignedSmallInteger('down_threshold')->default(1); // consecutive downs before alert
            $table->unsignedSmallInteger('notify_after_seconds')->default(0); // delay before first alert
            $table->unsignedSmallInteger('repeat_interval_minutes')->nullable(); // null = no repeats
            $table->boolean('notify_on_recovery')->default(true);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('monitor_policy_channel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_policy_id')->constrained('monitor_policies')->onDelete('cascade');
            $table->foreignId('notification_channel_id')->constrained('notification_channels')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['monitor_policy_id', 'notification_channel_id'], 'mpc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_policy_channel');
        Schema::dropIfExists('monitor_policies');
    }
};

