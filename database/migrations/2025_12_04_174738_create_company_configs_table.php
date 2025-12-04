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
        Schema::create('company_configs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            // Daily planning cutoff time (e.g. "18:00:00")
            $table->time('planning_cutoff_time')
                ->nullable();

            // Default waiting time per passenger stop in minutes
            $table->unsignedInteger('default_waiting_minutes')
                ->default(5);

            // Max number of drivers allowed per partner (0 = unlimited)
            $table->unsignedInteger('max_drivers_per_partner')
                ->default(0);

            // Whether drivers are allowed to reorder passengers in the manifest
            $table->boolean('allow_driver_reorder')
                ->default(true);

            // Flexible JSON settings for future flags
            $table->json('settings')
                ->nullable();

            $table->timestamps();

            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_configs');
    }
};
