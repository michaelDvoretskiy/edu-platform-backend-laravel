<?php

namespace App\Services;

use App\Models\General\Carousel;
use App\Models\General\CarouselItem;
use App\Models\General\MenuItem;

class InfoService
{
    public function getInfo($userId)
    {
        return [
            'info' => $this->getConfigInfo(),
            'menu' => $this->getMainMenuLinks(),
            'userMenu' => $this->getUserMenuLinks($userId),
            'socialLinks' => $this->getSocialLinks()
        ];
    }

    public function getCarousel($carouselName)
    {
        $carousel = Carousel::firstWhere('name', $carouselName);
        if (!$carousel) {
            return false;
        }

        $items = CarouselItem::where('carousel_id', $carousel->id)->orderBy('ord')->get();
        return $items->map(function($elem) {
            return [
                'title' => $elem->title,
                'content_text' => $elem->content_text,
                'img_path' => $elem->img_path,
                'btn_flag' => $elem->btn_flag,
                'link_type' => $elem->link->type,
                'link_title' => $elem->link->title,
                'link' => $elem->link->link,
                'link_params' => $elem->link->link_params,
                'icon_exists' => $elem->link->icon_exists,
                'icon_class' =>  $elem->link->icon_class
            ];
        });
    }

    private function getConfigInfo()
    {
        $configInfo = [];
        $configPureInfo = config('info');
        foreach ($configPureInfo as $key => $info) {
            if ($info['show'] ?? false) {
                if ($info['translate'] ?? false) {
                    $configInfo[$key] = __('info.' . $key);
                } else {
                    $configInfo[$key] = $info['content'] ?? '';
                }
            }
        }
        return $configInfo;
    }

    private function getMainMenuLinks()
    {
        $links = MenuItem::getMenuLinks('main_menu');
        return $links->map->only(['title', 'link', 'type'])->all();
    }

    private function getUserMenuLinks($userId) {
        if (!$userId) {
            return [];
        }
        $links = MenuItem::getMenuLinks('user_menu');
        return $links->map->only(['title', 'link', 'type'])->all();
    }

    private function getSocialLinks()
    {
        $links = MenuItem::getMenuLinks('social_links');
        return $links->map->only(['link', 'icon_class'])->all();
    }
}
