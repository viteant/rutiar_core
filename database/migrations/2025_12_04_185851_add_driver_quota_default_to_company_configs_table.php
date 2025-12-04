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
        Schema::table('company_configs', function (Blueprint $table) {
            // Cupo de conductores por socio por defecto a nivel compañía
            $table->unsignedInteger('driver_quota_default')
                ->default(0)
                ->after('allow_driver_reorder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_configs', function (Blueprint $table) {
            $table->dropColumn('driver_quota_default');
        });
    }
};
