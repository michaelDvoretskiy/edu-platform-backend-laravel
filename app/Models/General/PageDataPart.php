<?php

namespace App\Models\General;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class PageDataPart extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['content'];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
