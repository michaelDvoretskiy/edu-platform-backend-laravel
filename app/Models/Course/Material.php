<?php

namespace App\Models\Course;

use App\Models\General\Link;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Material extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['title'];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function link()
    {
        return $this->belongsTo(Link::class);
    }

    public function pdfStorage()
    {
        return $this->belongsTo(PdfStorage::class);
    }
}
