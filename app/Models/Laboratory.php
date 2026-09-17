<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

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
