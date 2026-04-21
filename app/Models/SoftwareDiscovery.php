<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SoftwareDiscovery extends Model
{
    use HasFactory;

    protected $fillable = [
        'computer_id',
        'raw_name',
        'version',
        'vendor',
        'install_date',
        'catalog_id',
    ];

    public function computer()
    {
        return $this->belongsTo(Computer::class);
    }

    public function catalog()
    {
        return $this->belongsTo(SoftwareCatalog::class, 'catalog_id');
    }

    /**
     * Alias for raw_name to match ComplianceReport column name
     */
    public function getSoftwareNameAttribute()
    {
        return $this->raw_name;
    }

    /**
     * Alias for version to match ComplianceReport column name
     */
    public function getSoftwareVersionAttribute()
    {
        return $this->version;
    }
}
