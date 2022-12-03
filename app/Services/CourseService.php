<?php

namespace App\Services;

use App\Models\Course\CourseCategory;
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
}
