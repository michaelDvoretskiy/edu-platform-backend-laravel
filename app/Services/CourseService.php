<?php

namespace App\Services;

use App\Models\Course\Course;
use App\Models\Course\CourseCategory;
use App\Models\Course\Lesson;
use App\Models\General\Link;
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
            'headerLink' => $this->getHeaderLinks('category'),
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
            'headerLink' => $this->getHeaderLinks('course'),
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
            'headerLink' => $this->getHeaderLinks('lesson', $lesson),
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

    private function getHeaderLinks($type, $object = null) {
        $homeLink = Link::firstWhere('name', 'home');
        $menu[] = [
            'type' => $homeLink->type,
            'title' => $homeLink->title,
            'link' => $homeLink->link,
            'link_params' => $homeLink->link_params,
        ];
        if (in_array($type, ['category','course','lesson'])) {
            $categoriesLink = Link::firstWhere('name', 'categories');
            $menu[] = [
                'type' => $categoriesLink->type,
                'title' => $categoriesLink->title,
                'link' => $categoriesLink->link,
                'link_params' => $categoriesLink->link_params,
            ];
        }
        if ($type == 'lesson') {
            $menu[] = [
                'type' => 'route',
                'title' => $object->course->title,
                'link' => 'Lessons',
                'link_params' => ['course' => $object->course->name],
            ];
        }

        return $menu;
    }
}
