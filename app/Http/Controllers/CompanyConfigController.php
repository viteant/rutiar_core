<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyConfig;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyConfigController extends Controller
{
    use AuthorizesRequests;
    use ValidatesRequests;

    /**
     * @throws AuthorizationException
     */
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
            // En caso de que algo legacy no haya creado config, la generamos con defaults
            $config = CompanyConfig::create([
                'company_id' => $company->id,
                'planning_cutoff_time' => '18:00:00',
                'default_waiting_minutes' => 5,
                'max_drivers_per_partner' => 0,
                'allow_driver_reorder' => true,
                'settings' => [],
            ]);
        }

        return response()->json([
            'data' => [
                'planning_cutoff_time' => optional($config->planning_cutoff_time)->format('H:i:s'),
                'default_waiting_minutes' => $config->default_waiting_minutes,
                'max_drivers_per_partner' => $config->max_drivers_per_partner,
                'allow_driver_reorder' => $config->allow_driver_reorder,
                'settings' => $config->settings ?? [],
            ],
        ]);
    }

    /**
     * @throws AuthorizationException
     */
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
            'max_drivers_per_partner' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'allow_driver_reorder' => ['nullable', 'boolean'],
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

        if (array_key_exists('max_drivers_per_partner', $validated)) {
            $config->max_drivers_per_partner = $validated['max_drivers_per_partner'];
        }

        if (array_key_exists('allow_driver_reorder', $validated)) {
            $config->allow_driver_reorder = $validated['allow_driver_reorder'];
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
