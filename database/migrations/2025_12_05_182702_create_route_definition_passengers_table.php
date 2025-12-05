<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('route_definition_passengers', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('route_definition_id')
                ->constrained('route_definitions')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('passenger_id')
                ->constrained('passengers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Orden lógico dentro de la ruta (1, 2, 3...)
            $table->unsignedInteger('pickup_order');

            // Hora estimada de recojo para este pasajero
            $table->time('planned_pickup_time')->nullable();

            // Dirección concreta usada en esta definición (puede diferir de la del passenger)
            $table->string('pickup_address', 255)->nullable();

            // Coordenadas opcionales para mapas
            $table->decimal('pickup_lat', 10, 7)->nullable();
            $table->decimal('pickup_lng', 10, 7)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Un mismo pasajero no debería aparecer dos veces en la misma definición
            $table->unique(['route_definition_id', 'passenger_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_definition_passengers');
    }

};
