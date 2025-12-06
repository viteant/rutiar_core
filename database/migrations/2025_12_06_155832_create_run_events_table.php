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
        Schema::create('run_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnUpdate();

            $table->foreignId('run_id')
                ->constrained('runs')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('route_definition_passenger_id')
                ->nullable()
                ->constrained('route_definition_passengers')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('passenger_id')
                ->nullable()
                ->constrained('passengers')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('event_type', 50);
            $table->string('incident_type', 50)->nullable();
            $table->string('source', 50)->default('driver_app');

            $table->timestampTz('occurred_at');

            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            $table->unsignedInteger('wait_seconds')->nullable();

            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['company_id', 'run_id']);
            $table->index(['run_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('run_events');
    }
};
