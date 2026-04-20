<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoftwareDiscovery extends Model
{
    //
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
}
