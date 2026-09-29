<?php

namespace App\Models;

use App\Models\Traits\ScopedByLaboratory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property bool $scan_requested
 * @property string $hostname
 * @property string|null $os_name
 * @property string|null $os_version
 * @property string|null $os_architecture
 * @property string|null $os_license_status
 * @property string|null $os_partial_key
 * @property string|null $processor
 * @property int|null $ram_gb
 * @property int|null $disk_total_gb
 * @property int|null $disk_free_gb
 * @property string|null $ip_address
 * @property string|null $mac_address
 * @property string|null $serial_number
 * @property string|null $manufacturer
 * @property string|null $model
 * @property string|null $location
 * @property int|null $laboratory_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $last_seen_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ComplianceReport> $complianceReports
 * @property-read int|null $compliance_reports_count
 * @property-read \App\Models\Laboratory|null $laboratory
 * @property-read \App\Models\ComplianceReport|null $latestComplianceReport
 * @property-read \App\Models\ScanSession|null $latestScanSession
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ScanSession> $scanSessions
 * @property-read int|null $scan_sessions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SoftwareDiscovery> $softwareDiscoveries
 * @property-read int|null $software_discoveries_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SoftwareDiscovery> $softwares
 * @property-read int|null $softwares_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer active()
 * @method static \Database\Factories\ComputerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer forLaboratory(string|int $laboratoryId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer forUserLab(?\App\Models\User $user = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereDiskFreeGb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereDiskTotalGb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereHostname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereLaboratoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereLastSeenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereMacAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereManufacturer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereOsArchitecture($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereOsLicenseStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereOsName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereOsPartialKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereOsVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereProcessor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereRamGb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereScanRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Computer extends Authenticatable
{
    use HasApiTokens, HasFactory, LogsActivity, ScopedByLaboratory;

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

    protected $casts = [
        'last_seen_at' => 'datetime',
        'scan_requested' => 'boolean',
    ];

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
