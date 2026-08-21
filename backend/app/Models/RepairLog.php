<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Repair log entry (FR-13). Agency-scoped.
 */
class RepairLog extends Model
{
    use BelongsToAgency;
    use HasFactory;

    public const SOURCE_INTERNAL = 'Internal Office';
    public const SOURCE_GSO = 'GSO Motorpool';
    public const SOURCE_EXTERNAL = 'External Repair Shop';

    public const SOURCES = [
        self::SOURCE_INTERNAL,
        self::SOURCE_GSO,
        self::SOURCE_EXTERNAL,
    ];

    /**
     * Sources whose supporting document is MANDATORY (FR-13, FR-14, 2026-08).
     *
     * Only the external shop. The GSO Motorpool is another City office rather
     * than a private business: its job order is worth keeping and can be
     * attached, but no money leaves the LGU and the requirements interviews
     * never established that it always issues paperwork the agency keeps.
     * Demanding it on that assumption would leave an administrator unable to
     * log the repair at all — no record instead of an imperfect one.
     *
     * If the Motorpool confirms a job order is always issued, add it here: the
     * form requests, both dashboards and the tests all read this list.
     */
    public const REQUIRED_DOCUMENT_SOURCES = [self::SOURCE_EXTERNAL];

    /** What each source is expected to produce, shown under the file input. */
    public const DOCUMENT_HINTS = [
        self::SOURCE_INTERNAL => 'Any supporting document (optional).',
        self::SOURCE_GSO => 'Job order or service slip from the GSO Motorpool, if one was issued.',
        self::SOURCE_EXTERNAL => 'Official receipt or sales invoice from the shop (required).',
    ];

    protected $fillable = [
        'agency_id',
        'vehicle_id',
        'driver_id',
        'repair_date',
        'scope_of_work',
        'parts_replaced',
        'cost',
        'repair_source',
        'external_shop_name',
        'receipt_path',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'repair_date' => 'date',
            'cost' => 'decimal:2',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /** REPAIR DATE label: "Mon j, Y". */
    public function dateLabel(): string
    {
        return $this->repair_date->format('M j, Y');
    }

    /** "₱12,500.00" or "—" when no cost was recorded. */
    public function costLabel(): string
    {
        return $this->cost !== null ? '₱'.number_format((float) $this->cost, 2) : '—';
    }

    /** SOURCE cell: the source, plus the external shop name when applicable. */
    public function sourceLabel(): string
    {
        if ($this->repair_source === self::SOURCE_EXTERNAL && $this->external_shop_name) {
            return $this->repair_source.' — '.$this->external_shop_name;
        }

        return $this->repair_source;
    }
}
