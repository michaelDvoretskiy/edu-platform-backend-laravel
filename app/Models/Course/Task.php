<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Task extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['title'];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function pdfStorage()
    {
        return $this->belongsTo(PdfStorage::class);
    }
}
