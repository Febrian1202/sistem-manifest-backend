<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceSnapshot extends Model
{
    use HasFactory;

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
