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
        Schema::create('run_gps_points', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnUpdate();

            $table->foreignId('run_id')
                ->constrained('runs')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestampTz('recorded_at');

            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);

            $table->decimal('speed_kmh', 6, 2)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['company_id', 'run_id']);
            $table->index(['run_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('run_gps_points');
    }
};
