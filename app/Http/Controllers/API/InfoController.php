<?php

namespace App\Http\Controllers\API;

use App\Services\InfoService;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;

class InfoController extends BaseController
{
    public function __construct(private InfoService $infoService) {}

    public function getInfo() {
        return $this->sendResponse(
            $this->infoService->getInfo(),
            'Info was sent successfully.'
        );
    }

    public function getHomeCarousel() {
        return $this->sendResponse(
            $this->infoService->getCarousel('homePage'),
            'Home carousel was sent successfully.'
        );
    }
}
