<?php

namespace App\Models\Course;

use App\Models\General\Link;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Course extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['title', 'description', 'link_title'];

    public function link()
    {
        return $this->belongsTo(Link::class);
    }
}
