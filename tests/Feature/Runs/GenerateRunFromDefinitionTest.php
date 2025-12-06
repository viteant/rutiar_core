<?php

namespace Tests\Feature\Runs;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Corporate;
use App\Models\Partner;
use App\Models\Passenger;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\Route as TransportRoute;
use App\Models\RouteDefinition;
use App\Models\RouteDefinitionPassenger;
use App\Models\Run;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GenerateRunFromDefinitionTest extends TestCase
{
    use RefreshDatabase;

    private const VIEW_RUNS_PERMISSION = 'view_runs';

    protected function giveRolePermission(Company $company, string $role, string $permissionName): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => $permissionName],
            ['description' => $permissionName]
        );

        RolePermission::factory()->create([
            'company_id'    => $company->id,
            'role'          => $role,
            'permission_id' => $permission->id,
        ]);
    }

    protected function giveUserPermission(Company $company, User $user, string $permissionName): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => $permissionName],
            ['description' => $permissionName]
        );

        UserPermission::factory()->create([
            'company_id'    => $company->id,
            'user_id'       => $user->id,
            'permission_id' => $permission->id,
        ]);
    }

    protected function createCompanyAdminWithPermissions(array $permissions): User
    {
        $company = Company::factory()->create();

        $user = User::factory()->for($company)->create([
            'role' => UserRole::COMPANY_ADMIN,
        ]);

        foreach ($permissions as $permissionName) {
            $this->giveRolePermission($company, UserRole::COMPANY_ADMIN->value, $permissionName);
        }

        return $user;
    }

    /**
     * @return array{company: Company, definition: RouteDefinition}
     */
    protected function createRouteDefinitionWithPassengers(int $passengerCount = 3): array
    {
        $company = Company::factory()->create();
        $corporate = Corporate::factory()->for($company)->create();
        $route = TransportRoute::factory()->for($company)->create();
        $partner = Partner::factory()->for($company)->create();

        $definition = RouteDefinition::factory()
            ->for($company)
            ->for($route, 'route')
            ->for($partner, 'partner')
            ->create([
                'base_fare_amount' => 15.75,
                'billing_code'     => 'BILL-001',
            ]);

        for ($i = 1; $i <= $passengerCount; $i++) {
            $passenger = Passenger::factory()
                ->for($company)
                ->for($corporate)
                ->create([
                    'shift_code'   => 'SHIFT-' . $i,
                    'home_address' => 'Home ' . $i,
                ]);

            RouteDefinitionPassenger::factory()
                ->for($company)
                ->for($definition, 'routeDefinition')
                ->for($passenger, 'passenger')
                ->create([
                    'pickup_order'        => $i,
                    'planned_pickup_time' => '08:0' . $i,
                ]);
        }

        return [
            'company'    => $company,
            'definition' => $definition,
        ];
    }

    public function test_company_admin_with_permission_can_generate_run_from_definition_with_snapshot(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_RUNS_PERMISSION,
        ]);

        $company = $admin->company;

        $setup = $this->createRouteDefinitionWithPassengers(3);
        $definition = $setup['definition'];

        // Aseguramos que la definición pertenece al mismo tenant
        $definition->update(['company_id' => $company->id]);

        Sanctum::actingAs($admin);

        $payload = [
            'route_definition_id' => $definition->id,
            'service_date'        => '2025-12-06',
            'fare_amount'         => 20.50,
        ];

        $response = $this->postJson('/api/runs/from-definition', $payload);

        $response->assertCreated();

        $runId = $response->json('id');

        $this->assertNotNull($runId);

        $this->assertDatabaseHas('runs', [
            'id'                  => $runId,
            'company_id'          => $company->id,
            'route_definition_id' => $definition->id,
            'service_date'        => '2025-12-06',
            'fare_amount'         => 20.50,
        ]);

        /** @var Run $run */
        $run = Run::query()->findOrFail($runId);

        $this->assertIsArray($run->manifest_snapshot);
        $this->assertSame($definition->id, $run->manifest_snapshot['route_definition_id'] ?? null);
        $this->assertSame('2025-12-06', $run->manifest_snapshot['service_date'] ?? null);

        $items = $run->manifest_snapshot['items'] ?? null;
        $this->assertIsArray($items);
        $this->assertCount(3, $items);

        $first = $items[0];

        $this->assertArrayHasKey('route_definition_passenger_id', $first);
        $this->assertArrayHasKey('passenger_id', $first);
        $this->assertArrayHasKey('pickup_order', $first);
        $this->assertArrayHasKey('planned_pickup_time', $first);
        $this->assertArrayHasKey('pickup_address', $first);
    }

    public function test_validation_fails_for_definition_from_another_tenant(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_RUNS_PERMISSION,
        ]);

        $otherSetup = $this->createRouteDefinitionWithPassengers(2);
        $foreignDefinition = $otherSetup['definition'];

        Sanctum::actingAs($admin);

        $payload = [
            'route_definition_id' => $foreignDefinition->id,
            'service_date'        => '2025-12-06',
        ];

        $response = $this->postJson('/api/runs/from-definition', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['route_definition_id']);
    }

    public function test_superadmin_can_generate_run_from_any_company_definition(): void
    {
        $superadmin = User::factory()->create([
            'company_id' => null,
            'role'       => UserRole::SUPERADMIN,
        ]);

        $setup = $this->createRouteDefinitionWithPassengers(2);
        $company = $setup['company'];
        $definition = $setup['definition'];

        Sanctum::actingAs($superadmin);

        $payload = [
            'route_definition_id' => $definition->id,
            'service_date'        => '2025-12-06',
        ];

        $response = $this->postJson('/api/runs/from-definition', $payload);

        $response->assertCreated();

        $runId = $response->json('id');

        $this->assertDatabaseHas('runs', [
            'id'                  => $runId,
            'company_id'          => $company->id,
            'route_definition_id' => $definition->id,
        ]);
    }

    public function test_snapshot_is_immutable_after_run_creation(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_RUNS_PERMISSION,
        ]);

        $company = $admin->company;

        $setup = $this->createRouteDefinitionWithPassengers(2);
        $definition = $setup['definition'];

        $definition->update(['company_id' => $company->id]);

        Sanctum::actingAs($admin);

        $payload = [
            'route_definition_id' => $definition->id,
            'service_date'        => '2025-12-06',
        ];

        $response = $this->postJson('/api/runs/from-definition', $payload);

        $response->assertCreated();

        $runId = $response->json('id');

        /** @var Run $runBefore */
        $runBefore = Run::query()->findOrFail($runId);
        $itemsBefore = $runBefore->manifest_snapshot['items'] ?? [];

        // Cambiamos la definición: desactivamos un pasajero y añadimos otro
        $existingItems = RouteDefinitionPassenger::query()
            ->where('route_definition_id', $definition->id)
            ->get();

        $firstItem = $existingItems->first();
        $firstItem->deactivate();

        $corporate = Corporate::factory()->for($company)->create();
        $newPassenger = Passenger::factory()
            ->for($company)
            ->for($corporate)
            ->create();

        RouteDefinitionPassenger::factory()
            ->for($company)
            ->for($definition, 'routeDefinition')
            ->for($newPassenger, 'passenger')
            ->create([
                'pickup_order'        => 99,
                'planned_pickup_time' => '09:00',
            ]);

        /** @var Run $runAfter */
        $runAfter = Run::query()->findOrFail($runId);
        $itemsAfter = $runAfter->manifest_snapshot['items'] ?? [];

        $this->assertCount(count($itemsBefore), $itemsAfter);
        $this->assertSame($itemsBefore, $itemsAfter);
    }

    public function test_missing_required_fields_fail_validation(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_RUNS_PERMISSION,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/runs/from-definition', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'route_definition_id',
            'service_date',
        ]);
    }

    public function test_user_override_permission_can_generate_run_even_without_role_permission(): void
    {
        $company = Company::factory()->create();

        $user = User::factory()->for($company)->create([
            'role' => UserRole::COMPANY_USER,
        ]);

        $this->giveUserPermission($company, $user, self::VIEW_RUNS_PERMISSION);

        $setup = $this->createRouteDefinitionWithPassengers(2);
        $definition = $setup['definition'];

        $definition->update(['company_id' => $company->id]);

        Sanctum::actingAs($user);

        $payload = [
            'route_definition_id' => $definition->id,
            'service_date'        => '2025-12-06',
        ];

        $response = $this->postJson('/api/runs/from-definition', $payload);

        $response->assertCreated();

        $runId = $response->json('id');

        $this->assertDatabaseHas('runs', [
            'id'                  => $runId,
            'company_id'          => $company->id,
            'route_definition_id' => $definition->id,
        ]);
    }
}
