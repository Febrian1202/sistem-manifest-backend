<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LicenseInventory extends Model
{
    //
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'license_key' => 'encrypted',
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
