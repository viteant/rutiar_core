<?php

namespace App\Models;

use App\Enums\RunEventSource;
use App\Enums\RunEventType;
use App\Enums\RunIncidentType;
use App\Models\Traits\Activatable;
use App\Models\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Builder
 * @property int $id
 * @property int $company_id
 * @property int $run_id
 * @property int|null $route_definition_passenger_id
 * @property int|null $passenger_id
 * @property RunEventType $event_type
 * @property RunIncidentType|null $incident_type
 * @property RunEventSource $source
 * @property Carbon $occurred_at
 * @property float|null $lat
 * @property float|null $lng
 * @property int|null $wait_seconds
 * @property string|null $notes
 * @property bool $is_active
 */
class RunEvent extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use Activatable;

    protected $table = 'run_events';

    protected $fillable = [
        'company_id',
        'run_id',
        'route_definition_passenger_id',
        'passenger_id',
        'event_type',
        'incident_type',
        'source',
        'occurred_at',
        'lat',
        'lng',
        'wait_seconds',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'occurred_at'   => 'datetime',
        'lat'           => 'decimal:7',
        'lng'           => 'decimal:7',
        'wait_seconds'  => 'integer',
        'is_active'     => 'boolean',
        'event_type'    => RunEventType::class,
        'incident_type' => RunIncidentType::class,
        'source'        => RunEventSource::class,
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
    }

    public function routeDefinitionPassenger(): BelongsTo
    {
        return $this->belongsTo(RouteDefinitionPassenger::class);
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(Passenger::class);
    }

    /**
     * Ensure notes is always trimmed.
     */
    protected function notes(): Attribute
    {
        return Attribute::make(
            set: static fn (?string $value): ?string => $value !== null ? trim($value) : null,
        );
    }
}
