<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Test\UserTest;
use App\Services\CourseService;
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
        $test = $test = UserTest::where(['test_id' => $id, 'user_id' => $user->id])->first();
        if (!$test) {
            return $this->sendError('No test found');
        }
        $oldAnswers = !is_null($test->answers) ? json_decode($test->answers, true) : [];
        $newAnswers = $request->all() ?? [];
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
        $test->save();

        return $this->sendResponse(
            $oldAnswers,
            'answers were stored successfully'
        );
    }
}
