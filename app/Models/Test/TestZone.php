<?php

namespace App\Models\Models\Test;

use App\Models\Test\TestZoneTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestZone extends Model
{
    use HasFactory;

    public function zomeTimes()
    {
        return $this->hasMany(TestZoneTime::class);
    }
}
