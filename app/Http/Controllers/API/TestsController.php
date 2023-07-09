<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
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
}
