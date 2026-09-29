<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $normalized_name
 * @property string|null $category
 * @property string $status
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read Collection<int, SoftwareDiscovery> $discoveries
 * @property-read int|null $discoveries_count
 * @property-read Collection<int, LicenseInventory> $licenses
 * @property-read int|null $licenses_count
 *
 * @method static \Database\Factories\SoftwareCatalogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareCatalog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareCatalog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareCatalog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareCatalog whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareCatalog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareCatalog whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareCatalog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareCatalog whereNormalizedName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareCatalog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoftwareCatalog whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class SoftwareCatalog extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'category', 'normalized_name'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName) => "Katalog software {$this->normalized_name} telah di-{$eventName}");
    }

    protected $fillable = [
        'normalized_name',
        'category',
        'status',
        'description',
    ];

    public function discoveries()
    {
        return $this->hasMany(SoftwareDiscovery::class, 'catalog_id');
    }

    public function licenses()
    {
        return $this->hasMany(LicenseInventory::class, 'catalog_id');
    }
}
