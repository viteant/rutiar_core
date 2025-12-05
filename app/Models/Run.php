<?php

namespace App\Models;

use App\Enums\RunStatus;
use App\Models\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Builder
 * @property int $id
 * @property int $company_id
 * @property int $route_definition_id
 * @property Carbon $service_date
 * @property RunStatus $status
 * @property int $partner_id
 * @property int $driver_id
 * @property int $vehicle_id
 * @property float $fare_amount
 * @property string $route_billing_code_snap
 * @property array|null $manifest_snapshot
 * @property int $created_by_user_id
 */
class Run extends Model
{
    use HasFactory;
    use BelongsToCompany;

    protected $table = 'runs';

    protected $fillable = [
        'company_id',
        'route_definition_id',
        'service_date',
        'status',
        'partner_id',
        'driver_id',
        'vehicle_id',
        'fare_amount',
        'route_billing_code_snap',
        'manifest_snapshot',
        'created_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'status' => RunStatus::class,
            'fare_amount' => 'decimal:2',
            'manifest_snapshot' => 'array',
        ];
    }

    public function routeDefinition(): BelongsTo
    {
        return $this->belongsTo(RouteDefinition::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Helper to know if the run can still be modified.
     */
    public function canBeEdited(): bool
    {
        /** @var RunStatus $status */
        $status = $this->status;

        return $status->canBeEdited();
    }
}
