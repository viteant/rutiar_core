<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPermission>
 */
class UserPermissionFactory extends Factory
{
    protected $model = UserPermission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            // This value will usually be overridden explicitly in tests.
            'permission_id' => 1,
        ];
    }
}
