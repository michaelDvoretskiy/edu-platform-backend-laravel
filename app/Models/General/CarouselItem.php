<?php

namespace App\Models\General;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class CarouselItem extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['title', 'content_text'];

    public function carousel()
    {
        return $this->belongsTo(Carousel::class);
    }

    public function link()
    {
        return $this->belongsTo(Link::class);
    }
}
