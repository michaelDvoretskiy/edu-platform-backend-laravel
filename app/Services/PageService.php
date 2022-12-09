<?php

namespace App\Services;

use App\Models\General\MenuItem;
use App\Models\General\Page;
use App\Models\General\TeamMember;

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

    public function getTeamMemberData($name) {
        $teamMember = TeamMember::firstWhere('name', $name);
        if (!$teamMember) {
            return false;
        }
        $menuItems = MenuItem::getMenuLinks($teamMember->menuItem->name);

        $teamMemberData =  [
            'title' => $teamMember->title,
            'profession' => $teamMember->profession,
            'title_full' => $teamMember->title_full,
            'profession_full' => $teamMember->profession_full,
            'img_path' => $teamMember->img_path,
            'descriptions' => $teamMember->teamMemberDescriptions->sortby('ord')->map(function($elem) {
                return ['title' => $elem->title, 'icon_exists' => $elem->icon_exists, 'icon_class' => $elem->icon_class];
            })
        ];
        $teamMemberData['links'] = $menuItems->map->only(['title', 'link', 'type', 'icon_class', 'icon_exists'])->all();

        return $teamMemberData;
    }
}
