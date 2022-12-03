<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Services\CourseService;

class CourseController extends BaseController
{
    public function __construct(private CourseService $courseService) {}

    public function showCategories() {
        $categoriesData = $this->courseService->getCategoriesList();
        if (!$categoriesData) {
            return $this->sendError('No categories found');
        }
        return $this->sendResponse($categoriesData,'Categories list was sent successfully');
    }
}
