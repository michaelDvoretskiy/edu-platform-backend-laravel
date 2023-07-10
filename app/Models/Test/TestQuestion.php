<?php

namespace App\Models\Test;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class TestQuestion extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['title'];

    public function section()
    {
        return $this->belongsTo(TestSection::class);
    }

    public function answers()
    {
        return $this->hasMany(TestAnswer::class, 'question_id');
    }
}
