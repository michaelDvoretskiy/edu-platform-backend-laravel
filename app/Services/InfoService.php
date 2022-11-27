<?php

namespace App\Services;

use App\Models\General\MenuItem;

class InfoService
{
    public function getMenuLinks($key) {
        $links = MenuItem::getMenuLinks($key);
        return $links->map->only(['title', 'link'])->all();
    }
}
