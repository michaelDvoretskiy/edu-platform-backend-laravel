<?php

namespace App\Services;

use App\Models\Test\TestQuestion;

class TestsService
{
    public function getOneQuestion($id) {
        $question = TestQuestion::find($id);
        return [
            "title" => $question->title,
        ];
    }
}
