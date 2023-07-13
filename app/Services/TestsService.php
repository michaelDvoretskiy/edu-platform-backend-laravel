<?php

namespace App\Services;

use App\Models\Test\Test;
use App\Models\Test\TestQuestion;
use App\Models\Test\UserTest;

class TestsService
{
    public function __construct(private TranslateService $translateService, private AccessService $accessService) {

    }

    public function getTest($id, $user) {
        $test = UserTest::where(['test_id' => $id, 'user_id' => $user->id])->first();
        if (!$test) {
            if (!$this->checkTestAvailability($id, $user->id)) {
                return false;
            }
            $res = $this->generateTest($id, $user);
            if (!$res) {
                return false;
            }
            $test = UserTest::where(['test_id' => $id, 'user_id' => $user->id])->first();
        }
        return $test->jsonView($this->translateService);
    }

    private function checkTestAvailability($testId, $userId) {
        return $this->accessService->testForUser($testId, $userId);
    }

    private function generateTest($id, $user) {
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
        $userTest = new UserTest();
        $userTest->test_id = $id;
        $userTest->user_id = $user->id;
        $userTest->title = json_encode($testData['title'], true);
        $userTest->status = 'new';
        $userTest->zones = json_encode($testData['zone'], true);
        $userTest->questions = json_encode($testData['questions'], true);
        $userTest->right_answers = json_encode($testData['rightAnswers'], true);
        $userTest->start_time = new \DateTime();
//        dd($userTest);
        $userTest->save();

        return true;
    }

    public function getOneQuestion($id) {
        $question = TestQuestion::find($id);
        return [
            "title" => $question->title,
        ];
    }
}
