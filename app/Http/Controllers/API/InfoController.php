<?php

namespace App\Http\Controllers\API;

use App\Services\InfoService;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Validator;

class InfoController extends BaseController
{
    public function __construct(private InfoService $infoService) {}

    public function getInfo() {
        return $this->sendResponse(
            $this->infoService->getInfo(),
            'User login successfully.'
        );
    }
}
