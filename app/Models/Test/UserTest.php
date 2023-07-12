<?php

namespace App\Models\Test;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTest extends Model
{
    use HasFactory;

    public function jsonView($translator)
    {
        return [
            'title' => $translator->translateArray(json_decode($this->title,true)),
            'zones' => json_decode($this->zones,true),
            'questions' => json_decode($this->questions,true),
            'answers' => json_decode($this->answers,true),
        ];
    }
}
