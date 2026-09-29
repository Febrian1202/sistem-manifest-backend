<?php

namespace App\Models;

use App\Models\Traits\ScopedByLaboratory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $laboratory_id
 * @property int|null $reviewed_by
 * @property string $report_type
 * @property string $period
 * @property \Illuminate\Support\Carbon|null $period_start
 * @property \Illuminate\Support\Carbon|null $period_end
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read \App\Models\Laboratory $laboratory
 * @property-read \App\Models\User|null $reviewer
 * @method static \Database\Factories\ReportApprovalFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval forLaboratory(string|int $laboratoryId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval forUserLab(?\App\Models\User $user = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereLaboratoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval wherePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval wherePeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval wherePeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereReportType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ReportApproval extends Model
{
    use HasFactory, LogsActivity, ScopedByLaboratory;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'notes', 'reviewed_at'])
            ->logOnlyDirty()
            ->useLogName('report_approval')
            ->setDescriptionForEvent(fn (string $eventName) => "Approval laporan kepatuhan telah di-{$eventName}");
    }

    protected $fillable = [
        'laboratory_id',
        'reviewed_by',
        'report_type',
        'period',
        'period_start',
        'period_end',
        'status',
        'notes',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
