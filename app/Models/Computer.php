<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Computer extends Authenticatable
{
    use HasApiTokens, HasFactory;

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
        'last_seen_at',
        'scan_requested',
    ];

    protected $casts = ['last_seen_at' => 'datetime'];

    public function softwares()
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
}
