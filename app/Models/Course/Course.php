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

    public function categories()
    {
        return $this->belongsToMany(
            CourseCategory::class,
            'course_category_courses',
            'course_id',
            'course_category_id'
        );
    }

    public function progLanguages()
    {
        return $this->belongsToMany(
            ProgLanguage::class,
            'course_prog_languages',
            'course_id',
            'prog_language_id'
        );
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }
}
