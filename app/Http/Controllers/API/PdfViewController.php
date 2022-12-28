<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Course\PdfStorage;
use App\Models\General\Page;
use App\Services\PageService;

class PdfViewController
{
    public function show($id) {
        return view('pdfviewer', ['pdfUrl' => 'http://localhost:8000/api/pdf/get-content/' . $id]);
    }

    public function getContent($id) {
        $pdf = PdfStorage::firstWhere('id', $id);
        $pdf_content = file_get_contents(storage_path($pdf->filePath));
        header("Content-Type: application/pdf");
        echo $pdf_content;
    }
}
