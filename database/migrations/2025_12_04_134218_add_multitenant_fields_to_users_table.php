<?php

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Partner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignIdFor(Company::class)
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();

            $table->foreignIdFor(Partner::class)
                ->nullable()
                ->after('company_id')
                ->constrained()
                ->nullOnDelete();

            $table->string('role', 32)->default(UserRole::COMPANY_USER->value);

            $table->boolean('is_active')
                ->after('role')
                ->default(true)
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropConstrainedForeignId('partner_id');
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
