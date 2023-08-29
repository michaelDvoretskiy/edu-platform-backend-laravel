<?php

namespace App\Models\Test;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTest extends Model
{
    use HasFactory;

    public function jsonView($translator)
    {
        $head = json_decode($this->head,true);
        $head['title'] = $translator->translateArray($head['title']);
        $json = [
            'head' => $head,
            'questions' => array_map(function($question) use ($translator) {
                $question['title'] = $translator->translateArray($question['title']);
                $question['answers'] = array_map(function($answer) use ($translator) {
                    $answer['title'] = $translator->translateArray($answer['title']);
                    return $answer;
                }, $question['answers']);
                return $question;
            }, json_decode($this->questions,true)),
            'answers' => json_decode($this->answers,true),
        ];
        $res = json_decode($this->result,true);
        if (!is_null($res)) {
            $json['result'] = $res;
        }
        return $json;
    }
}
