<?php

namespace App\Models\Course;

use App\Models\General\Link;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class CourseCategory extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['title', 'description'];

    public function link()
    {
        return $this->belongsTo(Link::class);
    }
}
