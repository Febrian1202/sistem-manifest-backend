<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laboratory extends Model
{
    use HasFactory;

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
