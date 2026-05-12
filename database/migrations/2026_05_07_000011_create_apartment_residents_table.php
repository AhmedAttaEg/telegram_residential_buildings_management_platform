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
        Schema::create('apartment_residents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->constrained()->cascadeOnDelete();
            $table->string('tenancy_type')->default('tenant');
            $table->string('occupancy_status')->default('active');
            $table->decimal('ownership_percentage', 5, 2)->default(0);
            $table->timestamp('move_in_at')->nullable();
            $table->timestamp('move_out_at')->nullable();
            $table->boolean('is_primary_contact')->default(false);
            $table->timestamps();

            $table->unique(['apartment_id', 'resident_id']);
            $table->index(['tenant_id', 'apartment_id']);
            $table->index(['tenant_id', 'resident_id']);
            $table->index(['tenant_id', 'occupancy_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartment_residents');
    }
};
