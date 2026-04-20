<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoftwareCatalog extends Model
{
    //
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
