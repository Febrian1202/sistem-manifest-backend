<?php

namespace App\Models;

use App\Models\Traits\ScopedByLaboratory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $computer_id
 * @property string $raw_name
 * @property string|null $version
 * @property string|null $vendor
 * @property string|null $install_date
 * @property int|null $catalog_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SoftwareCatalog|null $catalog
 * @property-read Computer $computer
 * @property-read mixed $software_name
 * @property-read mixed $software_version
 *
 * @method static \Database\Factories\SoftwareDiscoveryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareDiscovery forLaboratory(string|int $laboratoryId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareDiscovery forUserLab(?\App\Models\User $user = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareDiscovery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareDiscovery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareDiscovery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareDiscovery whereCatalogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareDiscovery whereComputerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareDiscovery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareDiscovery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareDiscovery whereInstallDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareDiscovery whereRawName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareDiscovery whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareDiscovery whereVendor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareDiscovery whereVersion($value)
 *
 * @mixin \Eloquent
 */
class SoftwareDiscovery extends Model
{
    use HasFactory, ScopedByLaboratory;

    protected $fillable = [
        'computer_id',
        'raw_name',
        'version',
        'vendor',
        'install_date',
        'catalog_id',
    ];

    public function computer()
    {
        return $this->belongsTo(Computer::class);
    }

    public function catalog()
    {
        return $this->belongsTo(SoftwareCatalog::class, 'catalog_id');
    }

    /**
     * Alias for raw_name to match ComplianceReport column name
     */
    public function getSoftwareNameAttribute()
    {
        return $this->raw_name;
    }

    /**
     * Alias for version to match ComplianceReport column name
     */
    public function getSoftwareVersionAttribute()
    {
        return $this->version;
    }
}
