<?php

namespace App\Models\Test;

use App\Models\Models\Test\TestZone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Test extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['title'];

    public function zone()
    {
        return $this->belongsTo(TestZone::class);
    }

    public function sections()
    {
        return $this->hasMany(TestSection::class);
    }
}
