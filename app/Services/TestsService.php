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

    public function calculateResult($test) {
        $rightAnswers = json_decode($test->right_answers, true);
        $givenAnswers = json_decode($test->answers, true) ?? [];
        $questions = json_decode($test->questions, true);
        $head = json_decode($test->head, true);

        $points = []; $allPPoints = 0;
        foreach($rightAnswers as $rightAnswer) {
            $key = array_search($rightAnswer['question'], array_column($givenAnswers, 'question'));
            if ($key === false) {
                $qAnswers = [];
            } else {
                $qAnswers = $givenAnswers[$key]['answers'];
            }

            $key = array_search($rightAnswer['question'], array_column($questions, 'id'));
            $answersCount = count($questions[$key]['answers']);

            $qRightAnswers = $rightAnswer['answers'];
            $rightAnswersCount = count($qRightAnswers);
            $point = ($rightAnswersCount - count(array_diff($qRightAnswers, $qAnswers))) / $rightAnswersCount;
            $point -= count(array_diff($qAnswers, $qRightAnswers)) / ($answersCount - $rightAnswersCount);
            if ($point < 0) {
                $point = 0;
            }

            $points[] = [
                'question' => $rightAnswer['question'],
                'points' => $point * $questions[$key]['points']
            ];
        }

        foreach ($points as $key => $value) {
            $points[$key]['normalizedPoints'] = $points[$key]['points'] * $head['points']['normalizer'];
            $allPPoints += $points[$key]['normalizedPoints'];
        }

        return [
            'result' => $allPPoints,
            'details' => $points
        ];
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
            'head' => [
                'title' => json_decode($test->getRawOriginal('title'),true),
                'zones' => [
                    'name' => $test->zone->name,
                    'times' => $test->zone->zomeTimes->map(function($zoneTime) {
                        return [
                            'minutes' => $zoneTime->minutes,
                            'koef' => $zoneTime->koef,
                        ];
                    })
                ],
            ]
        ];
        $allAvailablePoints = $test->points; $allAvailablePointsBySections = 0;
        $questions = [];
        foreach ($test->sections as $section) {
            $questionsAdded = 0;
            foreach ($section->questions->shuffle() as $question) {
                $questions[] =[
                    'q' => $question,
                    'points' => $section->points
                ];
                $questionsAdded++;
                if ($questionsAdded >= $section->questions_quantity) {
                    break;
                }
            }
        }
        foreach($questions as $questionData) {
            $question = $questionData['q'];
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
                })->count() > 1 ? 1 : 0,
                'points' => $questionData['points']
            ];
            $allAvailablePointsBySections += $questionData['points'];
            $testData['rightAnswers'][] = [
                'question' => $question->id,
                'answers' => array_values($question->answers->filter(function($elem) {
                    return $elem->is_right == 1;
                })->map(function($elem) {
                    return $elem->id;
                })->toArray())
            ];
        }
        $testData['head']['points'] = [
            'all' => $allAvailablePoints,
            'normalizer' => $allAvailablePoints / $allAvailablePointsBySections
        ];

        $userTest = new UserTest();
        $userTest->test_id = $id;
        $userTest->user_id = $user->id;
        $userTest->status = 'new';
        $userTest->head = json_encode($testData['head'], true);
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
