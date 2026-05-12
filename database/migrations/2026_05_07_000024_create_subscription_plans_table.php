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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status', 32)->default('active');
            $table->string('billing_cycle', 32);
            $table->decimal('price_amount', 14, 2);
            $table->string('currency', 8)->default('EGP');
            $table->unsignedInteger('trial_days')->nullable();
            $table->json('feature_limits')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['status']);
            $table->index(['billing_cycle']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
