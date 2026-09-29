<?php

namespace App\Models;

use App\Models\Traits\ScopedByLaboratory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $scan_session_id
 * @property int|null $catalog_id
 * @property string $raw_name
 * @property string|null $version
 * @property string|null $vendor
 * @property \Illuminate\Support\Carbon|null $install_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SoftwareCatalog|null $catalog
 * @property-read \App\Models\ScanSession $scanSession
 * @method static \Database\Factories\ScanSoftwareResultFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSoftwareResult forLaboratory(string|int $laboratoryId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSoftwareResult forUserLab(?\App\Models\User $user = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSoftwareResult newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSoftwareResult newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSoftwareResult query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSoftwareResult whereCatalogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSoftwareResult whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSoftwareResult whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSoftwareResult whereInstallDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSoftwareResult whereRawName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSoftwareResult whereScanSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSoftwareResult whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSoftwareResult whereVendor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSoftwareResult whereVersion($value)
 * @mixin \Eloquent
 */
class ScanSoftwareResult extends Model
{
    use HasFactory, ScopedByLaboratory;

    protected $fillable = [
        'scan_session_id',
        'catalog_id',
        'raw_name',
        'version',
        'vendor',
        'install_date',
    ];

    protected $casts = [
        'install_date' => 'date',
    ];

    public function scanSession(): BelongsTo
    {
        return $this->belongsTo(ScanSession::class);
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(SoftwareCatalog::class, 'catalog_id');
    }
}
