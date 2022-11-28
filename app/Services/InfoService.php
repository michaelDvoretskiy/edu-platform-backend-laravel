<?php

namespace App\Services;

use App\Models\General\MenuItem;

class InfoService
{
    public function getInfo() {
        return [
            'info' => $this->getConfigInfo(),
            'menu' => $this->getMainMenuLinks(),
            'socialLinks' => $this->getSocialLinks()
        ];
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

    private function getSocialLinks()
    {
        $links = MenuItem::getMenuLinks('social_links');
        return $links->map->only(['link', 'icon_class'])->all();
    }
}
