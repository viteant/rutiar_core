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
        Schema::create('route_definitions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('route_id')
                ->constrained('routes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('corporate_id')
                ->constrained('corporates')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('partner_id')
                ->constrained('partners')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('driver_id')
                ->nullable()
                ->constrained('drivers')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Versionado por combinación lógica (ruta + corporate + partner + dirección + hora)
            $table->unsignedInteger('version');

            $table->boolean('is_active')->default(true);

            $table->foreignId('previous_definition_id')
                ->nullable()
                ->constrained('route_definitions')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // En el ER es Enum run_direction; en BD lo dejamos como string y lo casteamos a enum en PHP
            $table->string('direction', 20);

            // Hora de referencia entrada/salida (puede ser null si la compañía define algo más laxo)
            $table->time('reference_time')->nullable();

            $table->string('billing_code', 50)->nullable();
            $table->decimal('base_fare_amount', 10, 2)->nullable();

            $table->timestamps();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_definitions');
    }
};
