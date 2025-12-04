<?php

namespace Tests\Feature\Auth;

use App\Models\Permission;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_permissions_config_has_unique_names(): void
    {
        $permissions = config('permissions.permissions', []);

        $names = array_column($permissions, 'name');
        $unique = array_unique($names);

        $this->assertCount(
            count($unique),
            $names,
            'Duplicate permission names found in config/permissions.php'
        );
    }

    public function test_permission_seeder_creates_permissions_from_config(): void
    {
        $this->seed(PermissionSeeder::class);

        $permissions = config('permissions.permissions', []);
        $names = array_column($permissions, 'name');

        // Aserción mínima: la lista no puede estar vacía
        $this->assertNotEmpty(
            $names,
            'config/permissions.php has no permissions defined. Add at least one.'
        );

        foreach ($names as $name) {
            $this->assertTrue(
                Permission::where('name', $name)->exists(),
                "Expected permission '{$name}' to be seeded."
            );
        }
    }
}
