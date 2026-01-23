<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Computer extends Model
{
    //
    protected $guarded = ['id'];
    protected $casts = ['last_seen_at' => 'datetime'];

    public function softwares()
    {
        return $this->hasMany(SoftwareDiscovery::class);
    }
}
