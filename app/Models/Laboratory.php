<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $building
 * @property string|null $floor
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read Collection<int, Computer> $computers
 * @property-read int|null $computers_count
 * @property-read Collection<int, User> $penanggungJawab
 * @property-read int|null $penanggung_jawab_count
 * @property-read Collection<int, ReportApproval> $reportApprovals
 * @property-read int|null $report_approvals_count
 *
 * @method static \Database\Factories\LaboratoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Laboratory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Laboratory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Laboratory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Laboratory whereBuilding($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Laboratory whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Laboratory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Laboratory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Laboratory whereFloor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Laboratory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Laboratory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Laboratory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Laboratory extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['faculty_id', 'name', 'code', 'building', 'floor', 'description'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName) => "Data laboratorium {$this->name} telah di-{$eventName}");
    }

    protected $fillable = [
        'faculty_id',
        'name',
        'code',
        'building',
        'floor',
        'description',
    ];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function computers(): HasMany
    {
        return $this->hasMany(Computer::class);
    }

    public function penanggungJawab(): HasMany
    {
        return $this->hasMany(User::class, 'laboratory_id');
    }

    public function reportApprovals(): HasMany
    {
        return $this->hasMany(ReportApproval::class);
    }

    public function scanSessions(): HasManyThrough
    {
        return $this->hasManyThrough(ScanSession::class, Computer::class);
    }
}
