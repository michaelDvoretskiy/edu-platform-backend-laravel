<?php

namespace App\Models\General;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['title'];

    public function pageDataParts()
    {
        return $this->hasMany(PageDataPart::class);
    }
}
