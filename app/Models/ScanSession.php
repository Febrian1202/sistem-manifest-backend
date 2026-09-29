<?php

namespace App\Models;

use App\Models\Traits\ScopedByLaboratory;
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
 * @property int|null $computer_id
 * @property string $scan_uuid
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property string $status
 * @property string $trigger
 * @property int $software_count
 * @property string|null $error_message
 * @property string|null $agent_version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read Collection<int, ComplianceSnapshot> $complianceSnapshots
 * @property-read int|null $compliance_snapshots_count
 * @property-read Computer|null $computer
 * @property-read Collection<int, ScanSoftwareResult> $softwareResults
 * @property-read int|null $software_results_count
 *
 * @method static \Database\Factories\ScanSessionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSession forLaboratory(string|int $laboratoryId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSession forUserLab(?\App\Models\User $user = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSession whereAgentVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSession whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSession whereComputerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSession whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSession whereScanUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSession whereSoftwareCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSession whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSession whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSession whereTrigger($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScanSession whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ScanSession extends Model
{
    use HasFactory, LogsActivity, ScopedByLaboratory;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['computer_id', 'scan_uuid', 'status', 'trigger', 'software_count'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName) => "Scan session {$this->scan_uuid} telah di-{$eventName}");
    }

    protected $fillable = [
        'computer_id',
        'scan_uuid',
        'started_at',
        'completed_at',
        'status',
        'trigger',
        'software_count',
        'error_message',
        'agent_version',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'software_count' => 'integer',
    ];

    public function computer(): BelongsTo
    {
        return $this->belongsTo(Computer::class);
    }

    public function softwareResults(): HasMany
    {
        return $this->hasMany(ScanSoftwareResult::class);
    }

    public function complianceSnapshots(): HasMany
    {
        return $this->hasMany(ComplianceSnapshot::class);
    }
}
