<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LicenseInventory extends Model
{
    //
    use HasFactory;

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

    /**
     * Get the masked license key.
     * Format: XXXX-XXXX-****-**** (Show first 2 segments, mask the rest)
     */
    public function getMaskedLicenseKeyAttribute()
    {
        if (empty($this->license_key)) {
            return '-';
        }

        $segments = explode('-', $this->license_key);
        $count = count($segments);

        $masked = [];
        for ($i = 0; $i < $count; $i++) {
            if ($i < 2) {
                $masked[] = $segments[$i];
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
