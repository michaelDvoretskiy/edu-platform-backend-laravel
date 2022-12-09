<?php

namespace App\Models\General;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class TeamMember extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['title','profession','title_full','profession_full'];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function teamMemberDescriptions()
    {
        return $this->hasMany(TeamMemberDescription::class);
    }
}
