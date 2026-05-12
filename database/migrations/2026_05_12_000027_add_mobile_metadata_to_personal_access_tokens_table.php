<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->string('device_name')->nullable()->after('name');
            $table->string('device_platform', 50)->nullable()->after('device_name');
            $table->string('app_version', 50)->nullable()->after('device_platform');
            $table->string('push_token', 512)->nullable()->after('app_version');
            $table->string('last_used_ip', 45)->nullable()->after('push_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn([
                'device_name',
                'device_platform',
                'app_version',
                'push_token',
                'last_used_ip',
            ]);
        });
    }
};
