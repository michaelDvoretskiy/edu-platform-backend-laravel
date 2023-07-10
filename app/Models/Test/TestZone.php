<?php

namespace App\Models\Test;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestZone extends Model
{
    use HasFactory;

    public function zomeTimes()
    {
        return $this->hasMany(TestZoneTime::class, 'zone_id', 'id');
    }
}
