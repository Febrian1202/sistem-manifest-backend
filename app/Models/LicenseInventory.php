<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

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
    public function catalog()
    {
        return $this->belongsTo(SoftwareCatalog::class, 'catalog_id');
    }
}
