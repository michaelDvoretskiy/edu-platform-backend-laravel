<?php

namespace App\Services;

use App\Models\General\MenuItem;
use App\Models\General\Page;

class PageService
{
    public function getPageData($pageName) {
        $page = Page::firstWhere('name', $pageName);
        if (!$page) {
            return false;
        }
        return [
            'title' => $page->title,
            'dataParts' => $page->pageDataParts->mapWithKeys(function($elem, $key) {
                return [$elem->name => $elem->content ? $elem->content : $elem->getTranslation('content', 'default')];
            })
        ];
    }
}
