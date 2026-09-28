<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanSoftwareResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_session_id',
        'catalog_id',
        'raw_name',
        'version',
        'vendor',
        'install_date',
    ];

    protected $casts = [
        'install_date' => 'date',
    ];

    public function scanSession(): BelongsTo
    {
        return $this->belongsTo(ScanSession::class);
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(SoftwareCatalog::class, 'catalog_id');
    }
}
