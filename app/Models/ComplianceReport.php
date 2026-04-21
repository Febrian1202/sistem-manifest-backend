<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceReport extends Model
{
    use HasFactory;

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
        'total_software_installed',
        'unlicensed_count',
        'blacklisted_count',
        'violation_details',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'detected_at' => 'datetime',
        'scanned_at' => 'datetime',
        'violation_details' => 'array',
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
