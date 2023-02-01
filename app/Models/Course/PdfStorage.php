<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class PdfStorage extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['filePath'];
}
