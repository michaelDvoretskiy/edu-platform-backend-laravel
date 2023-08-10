<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Test\UserTest;
use App\Services\TestsService;
use Illuminate\Http\Request;

class TestsController extends BaseController
{
    public function __construct(private TestsService $testsService) {}

    public function getQuestion($id) {
        return $this->sendResponse(
            $this->testsService->getOneQuestion($id),
            'Question was sent successfully'
        );
    }

    public function getUserTest(Request $request, $id) {
        $user = $request->user('sanctum');
        $test = $this->testsService->getTest($id, $user);
        if (!$test) {
            return $this->sendError('No test found');
        }
        return $this->sendResponse(
            $test,
            'Test was sent successfully'
        );
    }

    public function giveAnswer(Request $request, $id) {
        $user = $request->user('sanctum');
        $test = UserTest::where(['test_id' => $id, 'user_id' => $user->id])->first();
        if (!$test) {
            return $this->sendError('No test found');
        }

        $newAnswers = $request->all() ?? [];
        if (($request->overwrite ?? 0) == 0) {
            $oldAnswers = !is_null($test->answers) ? json_decode($test->answers, true) : [];

            foreach ($newAnswers as $newAnswer) {
                $oldFound = false;
                foreach ($oldAnswers as $oldKey => $oldAnswer) {
                    if ($newAnswer['question'] == $oldAnswer['question']) {
                        $oldAnswers[$oldKey]['answers'] = $newAnswer['answers'];
                        $oldFound = true;
                        break;
                    }
                }
                if (!$oldFound) {
                    $oldAnswers[] = $newAnswer;
                }
            }
            $test->answers = json_encode($oldAnswers, true);
        } else {
            $test->answers = json_encode($newAnswers, true);
        }

        $test->save();

        return $this->sendResponse(
            $oldAnswers,
            'answers were stored successfully'
        );
    }

    public function finishTest(Request $request, $id) {
        $user = $request->user('sanctum');
        $test = UserTest::where(['test_id' => $id, 'user_id' => $user->id])->first();
        if (!$test) {
            return $this->sendError('No test found');
        }

        $rightAnswers = json_decode($test->right_answers, true);
        $givenAnswers = json_decode($test->answers, true) ?? [];
        $questions = json_decode($test->questions, true);

        $points = [];
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

        dd($points);

        return $this->sendResponse(
            [
                $rightAnswers,
                $givenAnswers
            ],
            'Test was finished successfully'
        );



        $finishTime = new \DateTime();
    }
}
