<?php

namespace App\Models;

use App\Models\Traits\ScopedByLaboratory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $scan_session_id
 * @property int|null $computer_id
 * @property int|null $software_catalog_id
 * @property string $software_name
 * @property string|null $software_version
 * @property string $status
 * @property string|null $keterangan
 * @property int|null $license_inventory_id
 * @property Carbon|null $detected_at
 * @property Carbon $scanned_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Computer|null $computer
 * @property-read LicenseInventory|null $licenseInventory
 * @property-read ScanSession $scanSession
 * @property-read SoftwareCatalog|null $softwareCatalog
 *
 * @method static \Database\Factories\ComplianceSnapshotFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot forLaboratory(string|int $laboratoryId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot forUserLab(?\App\Models\User $user = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot whereComputerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot whereDetectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot whereKeterangan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot whereLicenseInventoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot whereScanSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot whereScannedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot whereSoftwareCatalogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot whereSoftwareName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot whereSoftwareVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComplianceSnapshot whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ComplianceSnapshot extends Model
{
    use HasFactory, ScopedByLaboratory;

    protected $fillable = [
        'scan_session_id',
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

    protected $casts = [
        'detected_at' => 'datetime',
        'scanned_at' => 'datetime',
    ];

    public function scanSession(): BelongsTo
    {
        return $this->belongsTo(ScanSession::class);
    }

    public function computer(): BelongsTo
    {
        return $this->belongsTo(Computer::class);
    }

    public function softwareCatalog(): BelongsTo
    {
        return $this->belongsTo(SoftwareCatalog::class, 'software_catalog_id');
    }

    public function licenseInventory(): BelongsTo
    {
        return $this->belongsTo(LicenseInventory::class, 'license_inventory_id');
    }
}
