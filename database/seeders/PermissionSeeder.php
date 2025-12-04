<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('permissions.permissions', []);

        foreach ($config as $item) {
            $name = Arr::get($item, 'name');

            if (!$name) {
                continue;
            }

            Permission::updateOrCreate(
                ['name' => $name],
                [
                    'description' => Arr::get($item, 'description'),
                ]
            );
        }
    }
}
