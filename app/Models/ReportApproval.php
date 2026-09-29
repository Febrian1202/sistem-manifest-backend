<?php

namespace App\Models;

use App\Models\Traits\ScopedByLaboratory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

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
