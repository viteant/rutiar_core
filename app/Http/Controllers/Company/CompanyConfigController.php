<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Company\UpdateCompanyConfigRequest;
use App\Models\Company;
use App\Models\CompanyConfig;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class CompanyConfigController extends BaseApiController
{
    /**
     * @throws AuthorizationException
     */
    public function show(): JsonResponse
    {
        $company = $this->resolveCompanyOrAbort('viewSettings');

        $config = $this->getOrCreateConfig($company);

        return response()->json([
            'data' => $this->serializeConfig($config),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateCompanyConfigRequest $request): JsonResponse
    {
        $company = $this->resolveCompanyOrAbort('updateSettings');

        $config = $this->getOrCreateConfig($company);

        $data = $request->validated();

        if (array_key_exists('planning_cutoff_time', $data)) {
            $config->planning_cutoff_time = $data['planning_cutoff_time']
                ? $data['planning_cutoff_time'] . ':00'
                : null;
        }

        foreach ([
                     'default_waiting_minutes',
                     'allow_driver_reorder',
                     'driver_quota_default',
                     'settings',
                 ] as $field) {
            if (array_key_exists($field, $data)) {
                $config->{$field} = $data[$field];
            }
        }

        $config->company()->associate($company);
        $config->save();

        return response()->json([
            'message' => 'Company configuration updated.',
        ]);
    }

    /**
     * Resolve the current company (tenant) and authorize the given ability.
     *
     * @throws AuthorizationException
     */
    protected function resolveCompanyOrAbort(string $ability): Company
    {
        $company = $this->tenant();

        if (! $company instanceof Company) {
            abort(403);
        }

        $this->authorize($ability, $company);

        return $company;
    }

    protected function getOrCreateConfig(Company $company): CompanyConfig
    {
        if ($company->config instanceof CompanyConfig) {
            return $company->config;
        }

        /** @var CompanyConfig */
        return CompanyConfig::create([
            'company_id'              => $company->id,
            'planning_cutoff_time'    => '18:00:00',
            'default_waiting_minutes' => 5,
            'allow_driver_reorder'    => true,
            'driver_quota_default'    => 0,
            'settings'                => [],
        ]);
    }

    protected function serializeConfig(CompanyConfig $config): array
    {
        return [
            'planning_cutoff_time'    => optional($config->planning_cutoff_time)?->format('H:i:s'),
            'default_waiting_minutes' => $config->default_waiting_minutes,
            'allow_driver_reorder'    => $config->allow_driver_reorder,
            'driver_quota_default'    => $config->driver_quota_default,
            'settings'                => $config->settings ?? [],
        ];
    }
}
