<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Test\Test;
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

        if ($this->testsService->isFinished($test)) {
            return $this->sendError('The test was already finished !');
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

        if ($this->testsService->isFinished($test)) {
            return $this->sendError('The test was already finished !');
        }

        $this->testsService->storeFinishDate($test);
        $points = $this->testsService->calculateResult($test);

        return $this->sendResponse(
            $points,
            'Test was finished successfully'
        );
    }

    public function moveToArchive(Request $request, $id) {
        $user = $request->user('sanctum');
        $test = UserTest::where(['test_id' => $id, 'user_id' => $user->id])->first();
        if (!$test) {
            return $this->sendError('No test found');
        }

        if (!$this->testsService->isFinished($test)) {
            return $this->sendError('The test is not finished !');
        }

        $originTest = Test::find($id);
        if (!$originTest) {
            return $this->sendError('No origin test found');
        }

        if($originTest->max_tries <= $this->testsService->triesSpended($id, $user->id) + 1) {
            return $this->sendError('All tries were spent');
        }

        $this->testsService->moveToArchive($test);

        return $this->sendResponse(
            [],
            'Test was moved to archive successfully'
        );
    }
}
