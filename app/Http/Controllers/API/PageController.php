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

    public function getTeamMember($name) {
        $teamMemberData = $this->pageService->getTeamMemberData($name);
        if (!$teamMemberData) {
            return $this->sendError('Team member not found');
        }
        return $this->sendResponse($teamMemberData,'Team member was sent successfully');
    }
}
