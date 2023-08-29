<?php

namespace App\Services;

use App\Models\Test\Test;
use App\Models\Test\TestQuestion;
use App\Models\Test\UserTest;
use App\Models\Test\UserTestHistory;

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
        $start = $this->getDate($test->started_at);
        $finish = $this->getDate($test->finished_at);
        $duration = ($finish->getTimestamp() - $start->getTimestamp()) / 60;

        $rightAnswers = json_decode($test->right_answers, true);
        $givenAnswers = json_decode($test->answers, true) ?? [];
        $questions = json_decode($test->questions, true);
        $head = json_decode($test->head, true);

        $filteredZones = array_filter($head['zones']['times'], function($elem) use ($duration) {
            return $elem['minutes'] < $duration;
        });
        usort($filteredZones, function($a, $b) {
            if ($a['minutes'] == $b['minutes']) {
                return 0;
            }
            return ($a['minutes'] > $b['minutes']) ? -1 : 1;
        });
        $filteredZones = array_values($filteredZones);
        $finishKoef = 1;
        if (count($filteredZones) > 0 ) {
            $finishKoef = $filteredZones[0]['koef'];
        }

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

        $testResult = [
            'result' => [
                'pointsSum' => $finishKoef == 0 ? 0 : $allPPoints,
                'zoneKoef' => $finishKoef,
                'durationMinutes' => $duration,
                'final' => $finishKoef * $allPPoints
            ],
            'details' => $finishKoef == 0 ? [] : $points
        ];

        $test->result = $testResult;
        $test->save();

        return $testResult;
    }

    public function moveToArchive($test) {
        $archive = new UserTestHistory();
        $archive->test_id = $test->test_id;
        $archive->user_id = $test->user_id;
        $archive->status = $test->status;
        $archive->started_at = $test->started_at;
        $archive->finished_at = $test->finished_at;
        $archive->head = $test->head;
        $archive->questions = $test->questions;
        $archive->answers = $test->answers;
        $archive->result = $test->result;
        $archive->right_answers = $test->right_answers;
        $archive->save();

        $test->delete();
    }

    private function checkTestAvailability($testId, $userId) {
        return $this->accessService->testForUser($testId, $userId);
    }

    private function generateTest($id, $user) {
        $test = Test::find($id);
        if (!$test) {
            return false;
        }

        $tryNumber = $this->triesSpended($id, $user->id) + 1;
        if ($tryNumber > $test->max_tries) {
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
                'tryNumber' => $tryNumber
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
        $userTest->started_at = new \DateTime();
//        dd($userTest);
        $userTest->save();

        return true;
    }

    public function isFinished($userTest) {
        if (!is_null($userTest->finished_at) && !is_null($userTest->result)) {
            return true;
        }
        return false;
    }

    public function triesSpended($testId, $userId) {
        return UserTestHistory::where([
            ['test_id', '=', $testId],
            ['user_id', '=', $userId]
        ])->count();
    }

    public function storeFinishDate($userTest) {
        $userTest->finished_at = new \DateTime();
        $userTest->save();
    }

    public function getOneQuestion($id) {
        $question = TestQuestion::find($id);
        return [
            "title" => $question->title,
        ];
    }

    private function getDate($date) {
        if (is_string($date)) {
            return new \DateTime($date);
        }
        return $date;
    }
}
