<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Computer extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $guarded = ['id'];
    protected $casts = ['last_seen_at' => 'datetime'];

    public function softwares()
    {
        return $this->hasMany(SoftwareDiscovery::class);
    }
}
