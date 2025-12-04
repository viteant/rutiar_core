<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'company_id' => null, // se setea en el test cuando toque
            'partner_id' => null,
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'), // para los tests
            'role' => UserRole::COMPANY_USER,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'company_id' => null,
            'role' => UserRole::SUPERADMIN,
        ]);
    }

    public function forCompany(?Company $company = null): static
    {
        return $this->state(function () use ($company) {
            $company ??= Company::factory()->create();

            return [
                'company_id' => $company->id,
            ];
        });
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
