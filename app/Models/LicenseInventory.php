<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $catalog_id
 * @property string|null $license_key
 * @property string|null $purchase_order_number
 * @property int $quota_limit
 * @property Carbon|null $purchase_date
 * @property Carbon|null $expiry_date
 * @property numeric|null $price_per_unit
 * @property string|null $notes
 * @property string|null $proof_image
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read SoftwareCatalog $catalog
 * @property-read mixed $masked_license_key
 *
 * @method static \Database\Factories\LicenseInventoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LicenseInventory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LicenseInventory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LicenseInventory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LicenseInventory whereCatalogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LicenseInventory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LicenseInventory whereExpiryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LicenseInventory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LicenseInventory whereLicenseKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LicenseInventory whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LicenseInventory wherePricePerUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LicenseInventory whereProofImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LicenseInventory wherePurchaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LicenseInventory wherePurchaseOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LicenseInventory whereQuotaLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LicenseInventory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class LicenseInventory extends Model
{
    //
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['catalog_id', 'purchase_order_number', 'quota_limit', 'purchase_date', 'expiry_date', 'price_per_unit', 'notes'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(function (string $eventName) {
                $softwareName = $this->catalog->normalized_name ?? 'N/A';

                return "Lisensi untuk software {$softwareName} (PO: {$this->purchase_order_number}) telah di-{$eventName}";
            });
    }

    protected $fillable = [
        'catalog_id',
        'purchase_order_number',
        'quota_limit',
        'purchase_date',
        'expiry_date',
        'price_per_unit',
        'notes',
        'proof_image',
        'license_key',
    ];

    protected $casts = [
        'license_key' => 'encrypted',
        'purchase_date' => 'date',
        'expiry_date' => 'date',
    ];

    protected $hidden = [
        'license_key',
    ];

    /**
     * Get the masked license key.
     * Format: XXXX-XXXX-****-**** (Show first 2 segments, mask the rest)
     */
    public function getMaskedLicenseKeyAttribute()
    {
        try {
            $key = $this->license_key;
        } catch (\Exception $e) {
            return 'Error Decrypting';
        }

        if (empty($key)) {
            return '-';
        }

        $segments = explode('-', $key);
        $count = count($segments);

        $masked = [];
        for ($i = 0; $i < $count; $i++) {
            if ($i < 2) {
                $masked[] = $segments[$i] ?? '****';
            } else {
                $masked[] = '****';
            }
        }

        return implode('-', $masked);
    }

    // Relasi ke Katalog
    public function catalog(): BelongsTo
    {
        return $this->belongsTo(SoftwareCatalog::class, 'catalog_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(LicenseAllocation::class);
    }

    public function activeAllocations(): HasMany
    {
        return $this->hasMany(LicenseAllocation::class)->where('status', 'active');
    }

    /**
     * Total kursi yang sudah dialokasikan ke semua fakultas.
     */
    public function getTotalAllocatedAttribute(): int
    {
        if ($this->relationLoaded('allocations')) {
            return (int) $this->allocations->where('status', 'active')->sum('allocated_quota');
        }

        return (int) $this->activeAllocations()->sum('allocated_quota');
    }

    /**
     * Sisa kursi universitas yang masih belum dialokasikan.
     */
    public function getRemainingUnallocatedAttribute(): int
    {
        return max(0, $this->quota_limit - $this->total_allocated);
    }
}
