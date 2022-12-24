<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Lesson extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['title', 'description'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function materials()
    {
        return $this->hasMany(Material::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
