<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Services\CourseService;
use Illuminate\Http\Request;

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

    public function showCategory($categoryName) {
        $categoryData = $this->courseService->getCategory($categoryName);
        if (!$categoryData) {
            return $this->sendError('No category found');
        }
        return $this->sendResponse($categoryData,'Category data was sent successfully');
    }

    public function showCourse(Request $request, $courseName) {
        $user = $request->user('sanctum');
        $courseData = $this->courseService->getCourse($courseName, $user);
        if (!$courseData) {
            return $this->sendError('No course found');
        }
        return $this->sendResponse($courseData,'Category data was sent successfully');
    }

    public function showLesson(Request $request, $lessonName) {
        $user = $request->user('sanctum');
        $lessonData = $this->courseService->getLesson($lessonName, $user);
        if (!$lessonData) {
            return $this->sendError('No lesson found');
        }
        return $this->sendResponse($lessonData,'Lesson data was sent successfully');
    }
}
