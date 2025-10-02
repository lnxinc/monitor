<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->string('type')->default('http')->after('name');
            $table->json('config')->nullable()->after('url');
            $table->string('webhook_token')->nullable()->after('config')->unique();
        });

        Schema::table('monitor_checks', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('error');
            $table->json('payload')->nullable()->after('meta');
        });
    }

    public function down(): void
    {
        Schema::table('monitor_checks', function (Blueprint $table) {
            $table->dropColumn(['meta', 'payload']);
        });

        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn(['type', 'config', 'webhook_token']);
        });
    }
};
