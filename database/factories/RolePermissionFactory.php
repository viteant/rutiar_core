<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\RolePermission;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RolePermission>
 */
class RolePermissionFactory extends Factory
{
    protected $model = RolePermission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'role' => $this->faker->randomElement(UserRole::cases())->value,
            // This value will usually be overridden explicitly in tests.
            'permission_id' => 1,
        ];
    }
}
