<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Course\PdfStorage;
use App\Models\General\Page;
use App\Services\AccessService;
use App\Services\PageService;
use Illuminate\Http\Request;

class PdfViewController
{
    public function __construct(private AccessService $accessService) {}

    public function show($id) {
        return view('pdfviewer', ['pdfUrl' => route('get-pdf-content',['id' => $id])]);
        //  'http://localhost:8000/api/pdf/get-content/' . $id
    }

    public function getContent(Request $request, $id) {
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
        echo $pdf_content;
    }
}
