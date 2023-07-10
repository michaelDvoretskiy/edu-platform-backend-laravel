<?php

namespace App\Services;

use App\Models\Test\Test;
use App\Models\Test\TestQuestion;

class TestsService
{
    public function generateTest($id) {
        // todo check if this test is available for the user
        $test = Test::find($id);
        if (!$test) {
            return false;
        }
        $testData = [
            'title' => json_decode($test->getRawOriginal('title'),true),
            'zone' => [
                'name' => $test->zone->name,
                'times' => $test->zone->zomeTimes->map(function($zoneTime) {
                    return [
                        'minutes' => $zoneTime->minutes,
                        'koef' => $zoneTime->koef,
                    ];
                })
            ],
        ];
        $questions = [];
        foreach ($test->sections as $section) {
            $questionsAdded = 0;
            foreach ($section->questions->shuffle() as $question) {
                $questions[] = $question;
                $questionsAdded++;
                if ($questionsAdded >= $section->questions_quantity) {
                    break;
                }
            }
        }
        foreach($questions as $question) {
            $testData['questions'][] = [
                'id' => $question->id,
                'title' => json_decode($question->getRawOriginal('title'), true),
                'answers' => $question->answers->shuffle()->map(function($answer) {
                    return [
                        'id' => $answer->id,
                        'title' => json_decode($answer->getRawOriginal('title'), true),
                    ];
                }),
                'multi' => $question->answers->filter(function($elem) {
                    return $elem->is_right == 1;
                })->count() > 1 ? 1 : 0
            ];
            $testData['rightAnswers'][] = [
                'question' => $question->id,
                'answers' => array_values($question->answers->filter(function($elem) {
                    return $elem->is_right == 1;
                })->map(function($elem) {
                    return $elem->id;
                })->toArray())
            ];
        }
        return $testData;
    }

    public function getOneQuestion($id) {
        $question = TestQuestion::find($id);
        return [
            "title" => $question->title,
        ];
    }
}
