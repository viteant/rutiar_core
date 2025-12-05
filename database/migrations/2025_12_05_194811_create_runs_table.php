<?php

use App\Enums\RunStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('runs', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnUpdate();

            $table->foreignId('route_definition_id')
                ->constrained('route_definitions')
                ->cascadeOnUpdate();

            $table->date('service_date');

            $table->string('status', 50)
                ->default(RunStatus::PLANNED->value);

            $table->foreignId('partner_id')
                ->constrained('partners')
                ->cascadeOnUpdate();

            $table->foreignId('driver_id')
                ->nullable()
                ->constrained('drivers')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('vehicle_id')
                ->nullable()
                ->constrained('vehicles')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->decimal('fare_amount', 10, 2)
                ->nullable();

            $table->string('route_billing_code_snap', 50)
                ->nullable();

            $table->json('manifest_snapshot')
                ->nullable()
                ->comment('JSON snapshot of passengers/order/times for this day');

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('runs');
    }
};
