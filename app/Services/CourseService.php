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
                'name' => $elem->name,
                'title' => $elem->title,
                'content_text' => $elem->description,
                'img_path' => $elem->img_path,
                'link_title' => __('info.' . 'read more'),
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
                    'link_title' => __('info.' . 'read more'),
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
            'img_path' => $course->img_path,
            'progLanguages' => implode(",", $course->progLanguages->map(function($elem) {
                return $elem->name;
            })->toArray()),
            'lessons' => $course->lessons->sortby('ord')->map(function($elem) {
                return [
                    'title' => $elem->title,
                    'name' => $elem->name,
                    'description' => $elem->description,
                    'link_title' => __('info.' . 'read more'),
                    'languages' => json_decode($elem->languages, true),
                    'hasPdf' => $elem->materials->filter(function($item) {
                        return $item['type'] == 'pdf';
                    })->count() > 0 ? true : false,
                    'hasVideo' => $elem->materials->filter(function($item) {
                        return $item['type'] == 'video';
                    })->count() > 0 ? true : false,
                    'hasTasks' => $elem->tasks->count() > 0 ? true : false,
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
