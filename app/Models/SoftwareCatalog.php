<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SoftwareCatalog extends Model
{
    use HasFactory;

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
