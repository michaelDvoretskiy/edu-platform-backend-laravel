<?php

namespace App\Models\General;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class TeamMemberDescription extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['title'];
}
