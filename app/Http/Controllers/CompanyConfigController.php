<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyConfig;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyConfigController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Company|null $company */
        $company = $user->company;

        if (!$company) {
            abort(403);
        }

        $this->authorize('viewSettings', $company);

        /** @var CompanyConfig|null $config */
        $config = $company->config;

        if (!$config) {
            $config = CompanyConfig::create([
                'company_id' => $company->id,
                'planning_cutoff_time' => '18:00:00',
                'default_waiting_minutes' => 5,
                'allow_driver_reorder' => true,
                'driver_quota_default' => 0,
                'settings' => [],
            ]);
        }

        return response()->json([
            'data' => [
                'planning_cutoff_time' => optional($config->planning_cutoff_time)->format('H:i:s'),
                'default_waiting_minutes' => $config->default_waiting_minutes,
                'allow_driver_reorder' => $config->allow_driver_reorder,
                'driver_quota_default' => $config->driver_quota_default,
                'settings' => $config->settings ?? [],
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Company|null $company */
        $company = $user->company;

        if (!$company) {
            abort(403);
        }

        $this->authorize('updateSettings', $company);

        /** @var CompanyConfig|null $config */
        $config = $company->config;

        if (!$config) {
            $config = new CompanyConfig([
                'company_id' => $company->id,
            ]);
        }

        $validated = $request->validate([
            'planning_cutoff_time' => ['nullable', 'date_format:H:i'],
            'default_waiting_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'allow_driver_reorder' => ['nullable', 'boolean'],
            'driver_quota_default' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'settings' => ['nullable', 'array'],
        ]);

        if (array_key_exists('planning_cutoff_time', $validated)) {
            $time = $validated['planning_cutoff_time'];
            $config->planning_cutoff_time = $time
                ? $time . ':00'
                : null;
        }

        if (array_key_exists('default_waiting_minutes', $validated)) {
            $config->default_waiting_minutes = $validated['default_waiting_minutes'];
        }

        if (array_key_exists('allow_driver_reorder', $validated)) {
            $config->allow_driver_reorder = $validated['allow_driver_reorder'];
        }

        if (array_key_exists('driver_quota_default', $validated)) {
            $config->driver_quota_default = $validated['driver_quota_default'];
        }

        if (array_key_exists('settings', $validated)) {
            $config->settings = $validated['settings'];
        }

        $config->company_id = $company->id;
        $config->save();

        return response()->json([
            'message' => 'Company configuration updated.',
        ]);
    }
}
