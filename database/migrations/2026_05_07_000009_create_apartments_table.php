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
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('unit_number');
            $table->string('occupancy_status')->default('vacant');
            $table->string('status')->default('active');
            $table->integer('floor_number')->nullable();
            $table->string('unit_type')->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->decimal('area_value', 10, 2)->nullable();
            $table->string('area_unit', 16)->nullable();
            $table->timestamps();

            $table->unique(['building_id', 'unit_number']);
            $table->index(['tenant_id', 'building_id']);
            $table->index(['tenant_id', 'occupancy_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
