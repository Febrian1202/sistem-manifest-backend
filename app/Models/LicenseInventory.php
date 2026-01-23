<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LicenseInventory extends Model
{
    //
    use HasFactory;

    protected $guarded = ['id'];

    // Relasi ke Katalog
    public function catalog()
    {
        return $this->belongsTo(SoftwareCatalog::class, 'catalog_id');
    }
}
