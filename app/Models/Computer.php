<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Computer extends Authenticatable
{
    use HasApiTokens, HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['hostname', 'location', 'ip_address', 'os_name', 'os_license_status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName) => "Data komputer {$this->hostname} telah di-{$eventName}");
    }

    protected $attributes = [
        'status' => 'active',
    ];

    protected $fillable = [
        'hostname',
        'os_name',
        'os_version',
        'os_architecture',
        'os_license_status',
        'os_partial_key',
        'processor',
        'ram_gb',
        'disk_total_gb',
        'disk_free_gb',
        'ip_address',
        'mac_address',
        'serial_number',
        'manufacturer',
        'model',
        'location',
        'laboratory_id',
        'status',
        'last_seen_at',
        'scan_requested',
    ];

    protected $casts = ['last_seen_at' => 'datetime'];

    protected $hidden = [
        'mac_address',
        'serial_number',
        'ip_address',
    ];

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function softwares()
    {
        return $this->hasMany(SoftwareDiscovery::class);
    }

    public function softwareDiscoveries()
    {
        return $this->hasMany(SoftwareDiscovery::class);
    }

    public function complianceReports()
    {
        return $this->hasMany(ComplianceReport::class);
    }

    public function latestComplianceReport()
    {
        return $this->hasOne(ComplianceReport::class)->latestOfMany();
    }

    public function scanSessions()
    {
        return $this->hasMany(ScanSession::class);
    }

    public function latestScanSession()
    {
        return $this->hasOne(ScanSession::class)->latestOfMany();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
