<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceReport extends Model
{
    //
    use HasFactory;

    protected $guarded = ['id'];

    // Casting JSON agar otomatis jadi Array saat diambil
    protected $casts = [
        'violation_details' => 'array',
        'scanned_at' => 'datetime',
    ];

    public function computer()
    {
        return $this->belongsTo(Computer::class);
    }
}
