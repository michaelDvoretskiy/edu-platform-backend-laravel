<?php

namespace App\Services;

use App\Models\Course\Course;
use App\Models\Course\CourseCategory;
use App\Models\Course\Lesson;
use App\Models\General\MenuItem;
use App\Models\General\Page;

class CourseService
{
    public function getCategoriesList() {
        $categories = CourseCategory::orderBy('ord')->get();
        if (!$categories) {
            return false;
        }
        return $categories->map(function($elem) {
            return [
                'title' => $elem->title,
                'content_text' => $elem->description,
                'img_path' => $elem->img_path,
                'link_type' => $elem->link->type,
                'link_title' => $elem->link->title,
                'link' => $elem->link->link,
                'link_params' => $elem->link->link_params,
                'icon_exists' => $elem->link->icon_exists,
                'icon_class' =>  $elem->link->icon_class
            ];
        });
    }

    public function getCategory($name) {
        $category = CourseCategory::firstWhere('name', $name);
        if (!$category) {
            return false;
        }
        return [
            'title' => $category->title,
            'description' => $category->description,
            'img_path' => $category->img_path,
            'courses' => $category->courses->sortby('ord')->map(function($elem) {
                return [
                    'title' => $elem->title,
                    'name' => $elem->name,
                    'description' => $elem->description,
                    'img_path' => $elem->img_path,
                    'link_type' => $elem->link->type,
                    'link_title' => $elem->link->title,
                    'link' => $elem->link->link,
                    'link_params' => $elem->link->link_params,
                    'icon_exists' => $elem->link->icon_exists,
                    'icon_class' =>  $elem->link->icon_class,
                    'progLanguages' => implode(",", $elem->progLanguages->map(function($elem) {
                        return $elem->name;
                    })->toArray()),
                ];
            })
        ];
    }

    public function getCourse($name) {
        $course = Course::firstWhere('name', $name);
        if (!$course) {
            return false;
        }
        return [
            'title' => $course->title,
            'description' => $course->description,
            'name' => $course->name,
            'progLanguages' => implode(",", $course->progLanguages->map(function($elem) {
                return $elem->name;
            })->toArray()),
            'lessons' => $course->lessons->sortby('ord')->map(function($elem) {
                return [
                    'title' => $elem->title,
                    'name' => $elem->name,
                    'description' => $elem->description,
                    'languages' => json_decode($elem->languages, true),
                ];
            })
        ];
    }

    public function getLesson($name) {
        $lesson = Lesson::firstWhere('name', $name);
        if (!$lesson) {
            return false;
        }
        return [
            'title' => $lesson->title,
            'description' => $lesson->description,
            'name' => $lesson->name,
            'languages' => json_decode($lesson->languages, true),
            'materials' => $lesson->materials->sortby('ord')->map(function($elem) {
                return [
                    'title' => $elem->title,
                    'type' => $elem->type,
                    'link' => $elem->link?->link,
                    'file' => $elem->pdfStorage?->id,
                ];
            }),
            'tasks' => $lesson->tasks->sortby('ord')->map(function($elem) {
                return [
                    'title' => $elem->title,
                    'file' => $elem->pdfStorage->id,
                    'points' => $elem->points,
                    'weight' => $elem->weight,
                ];
            }),
        ];
    }
}
