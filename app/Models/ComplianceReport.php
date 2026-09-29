<?php

namespace App\Models;

use App\Models\Traits\ScopedByLaboratory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $computer_id
 * @property int $software_catalog_id
 * @property string $software_name
 * @property string|null $software_version
 * @property string $status
 * @property string $keterangan
 * @property int|null $license_inventory_id
 * @property \Illuminate\Support\Carbon $detected_at
 * @property \Illuminate\Support\Carbon $scanned_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Computer $computer
 * @property-read \App\Models\LicenseInventory|null $licenseInventory
 * @property-read \App\Models\SoftwareCatalog $softwareCatalog
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport byComputer($computerId)
 * @method static \Database\Factories\ComplianceReportFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport forLaboratory(string|int $laboratoryId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport forUserLab(?\App\Models\User $user = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport nonCompliant()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport whereComputerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport whereDetectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport whereKeterangan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport whereLicenseInventoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport whereScannedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport whereSoftwareCatalogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport whereSoftwareName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport whereSoftwareVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceReport whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ComplianceReport extends Model
{
    use HasFactory, ScopedByLaboratory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'computer_id',
        'software_catalog_id',
        'software_name',
        'software_version',
        'status',
        'keterangan',
        'license_inventory_id',
        'detected_at',
        'scanned_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'detected_at' => 'datetime',
        'scanned_at' => 'datetime',
    ];

    /**
     * Get the computer that owns the report.
     */
    public function computer(): BelongsTo
    {
        return $this->belongsTo(Computer::class);
    }

    /**
     * Get the software catalog associated with the report.
     */
    public function softwareCatalog(): BelongsTo
    {
        return $this->belongsTo(SoftwareCatalog::class);
    }

    /**
     * Get the license inventory associated with the report.
     */
    public function licenseInventory(): BelongsTo
    {
        return $this->belongsTo(LicenseInventory::class);
    }

    /**
     * Scope a query to only include non-compliant records.
     */
    public function scopeNonCompliant($query)
    {
        return $query->where('status', 'Tidak Berlisensi');
    }

    /**
     * Scope a query to filter by computer.
     */
    public function scopeByComputer($query, $computerId)
    {
        return $query->where('computer_id', $computerId);
    }
}
