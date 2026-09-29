<?php

namespace App\Models;

use App\Models\Traits\ScopedByLaboratory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

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
