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
        Schema::table('partners', function (Blueprint $table) {
            // Tax ID / RUC / identificación fiscal
            $table->string('tax_id', 50)
                ->nullable()
                ->after('name');

            // Cupo máximo de conductores para este socio
            // NULL o 0 = usa la política por defecto de la compañía
            $table->unsignedInteger('driver_quota')
                ->nullable()
                ->after('tax_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn(['tax_id', 'driver_quota']);
        });
    }
};
