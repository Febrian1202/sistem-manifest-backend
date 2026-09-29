<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $building
 * @property string|null $floor
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Computer> $computers
 * @property-read int|null $computers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $penanggungJawab
 * @property-read int|null $penanggung_jawab_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReportApproval> $reportApprovals
 * @property-read int|null $report_approvals_count
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
 * @mixin \Eloquent
 */
class Laboratory extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'building', 'floor', 'description'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName) => "Data laboratorium {$this->name} telah di-{$eventName}");
    }

    protected $fillable = [
        'name',
        'code',
        'building',
        'floor',
        'description',
    ];

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
}
