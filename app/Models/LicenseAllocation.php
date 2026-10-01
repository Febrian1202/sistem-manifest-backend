<?php

namespace App\Models;

use Database\Factories\LicenseAllocationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class LicenseAllocation extends Model
{
    /** @use HasFactory<LicenseAllocationFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'license_inventory_id',
        'faculty_id',
        'allocated_quota',
        'allocation_date',
        'start_date',
        'end_date',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'allocation_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'allocated_quota' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(function (string $eventName) {
                $softwareName = $this->licenseInventory?->catalog?->normalized_name ?? 'N/A';
                $facultyName = $this->faculty?->name ?? 'N/A';

                return "Alokasi lisensi {$softwareName} untuk fakultas {$facultyName} telah di-{$eventName}";
            });
    }

    public function licenseInventory(): BelongsTo
    {
        return $this->belongsTo(LicenseInventory::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForFaculty(Builder $query, int $facultyId): Builder
    {
        return $query->where('faculty_id', $facultyId);
    }
}
