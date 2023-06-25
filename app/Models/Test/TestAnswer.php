<?php

namespace App\Models\Test;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class TestAnswer extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['title'];

    public function question()
    {
        return $this->belongsTo(TestQuestion::class);
    }
}
