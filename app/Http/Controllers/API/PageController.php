<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\General\Page;
use App\Services\PageService;

class PageController extends BaseController
{
    public function __construct(private PageService $pageService) {}

    public function show($pageName) {
        $pageData = $this->pageService->getPageData($pageName);
        if (!$pageData) {
            return $this->sendError('Page not found');
        }
        return $this->sendResponse($pageData,'Page data was sent successfully');
    }
}
