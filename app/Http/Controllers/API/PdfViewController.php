<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Course\PdfStorage;
use App\Models\General\Page;
use App\Services\AccessService;
use App\Services\PageService;
use Illuminate\Http\Request;

class PdfViewController extends BaseController
{
    public function __construct(private AccessService $accessService) {}

    public function show($id) {
//        return view('pdfviewer', ['pdfUrl' => route('get-pdf-content',['id' => $id])]);

//        $v = view('pdfviewer', ['pdfUrl' => route('get-pdf-content', ['id' => $id])])->render();
//        return $this->sendResponse(['html'=>$v], "");
        return $this->sendResponse("Huray!", "Huray!");
    }

    public function getContent(Request $request, $id, $type) {
        $user = $request->user('sanctum');
        if (!$this->accessService->isPdfAvailableForUser($id, $user, $type)) {
            return $this->sendError("No data available");
        }
        $pdf = PdfStorage::firstWhere('id', $id);
        if (!$pdf) {
            return $this->sendError("No data available");
        }
        $filePath = $pdf->filePath ? $pdf->filePath : $pdf->getTranslation('filePath', 'default');
        $pdf_content = base64_encode(file_get_contents(storage_path($filePath)));

        return $this->sendResponse(['content'=>$pdf_content], "");
    }

    public function getContent2(Request $request, $id) {
        $user = $request->user('sanctum');
        if (!$this->accessService->isPdfAvailableForUser($id, $user)) {
            echo "No data awailable";
            return;
        }
        $pdf = PdfStorage::firstWhere('id', $id);
        if (!$pdf) {
            echo "No data awailable";
            return;
        }
        $pdf_content = file_get_contents(storage_path($pdf->filePath));
        header("Content-Type: application/pdf");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header('Access-Control-Allow-Credentials: true');
        echo $pdf_content;
    }

    public function getSubscribeDetails() {
        $path = storage_path("txt/details-".app()->getLocale().".txt");
        header("Content-Type: application/octet-stream");    //
        header("Content-Length: " . filesize($path));
        header('Content-Disposition: attachment; filename=details.txt');
        readfile($path);
    }
}
