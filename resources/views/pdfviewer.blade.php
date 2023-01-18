<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        body{
            background: black;
            margin:0px;
        }
        .pagination {
            position: fixed;
            background: #ffffff;
            width: 100%;
            float: left;
            padding: 5px;
            border: solid 1px black;
        }
        .pagination .wrap{
            float:right;
            width: 400px;
        }
        #the-canvas {
            border: 1px solid black;
            direction: ltr;
            margin: 0 auto;
            display: block;
        }
        @media print {
            body {display:none;}
        }
    </style>
    <?php
    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        // should do a check here to match $_SERVER['HTTP_ORIGIN'] to a
        // whitelist of safe domains
//        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }
    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    ?>
</head>
<body>
<div class="pagination">
    <div class="wrap">
        Scale:
        <select name="scale" id="scale" onChange="renderPage(pageNum);" style="margin-right:50px;">
            <option value="0.5">0.5</option>
            <option value="1">1</option>
            <option value="1.5" selected>1.5</option>
            <option value="2">2</option>
        </select>
        <button id="prev">Previous</button>
        <button id="next">Next</button>

        &nbsp; &nbsp;
        <span>Page: <span id="page_num"></span> /
        <span id="page_count"></span></span>
    </div>
</div>

<canvas id="the-canvas"></canvas>

<script src="https://mozilla.github.io/pdf.js/build/pdf.js"></script>
<script>

    document.addEventListener('contextmenu', event => event.preventDefault());

    // Disable Canvas Image Downloading
    document.addEventListener('contextmenu', event => event.preventDefault());

    // If absolute URL from the remote server is provided, configure the CORS
    // header on that server.
    var url = '{{ $pdfUrl }}'; // your file location and file name with ext.


    // Loaded via <script> tag, create shortcut to access PDF.js exports.
    var pdfjsLib = window['pdfjs-dist/build/pdf'];

    // The workerSrc property shall be specified.
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://mozilla.github.io/pdf.js/build/pdf.worker.js';
    // pdfjsLib.GlobalWorkerOptions.workerSrc = 'pdf.worker.js';

    var pdfDoc = null,
        pageNum = 1,
        pageRendering = false,
        pageNumPending = null,
        scale = 1.5,
        canvas = document.getElementById('the-canvas'),
        ctx = canvas.getContext('2d');

    /**
     * Get page info from document, resize canvas accordingly, and render page.
     * @param num Page number.
     */
    function renderPage(num) {
        pageRendering = true;
        // Using promise to fetch the page
        pdfDoc.getPage(num).then(function(page) {
            var viewport = page.getViewport({scale: document.getElementById('scale').value});
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            // Render PDF page into canvas context
            var renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };
            var renderTask = page.render(renderContext);

            // Wait for rendering to finish
            renderTask.promise.then(function() {
                pageRendering = false;
                if (pageNumPending !== null) {
                    // New page rendering is pending
                    renderPage(pageNumPending);
                    pageNumPending = null;
                }
            });
        });

        // Update page counters
        document.getElementById('page_num').textContent = num;
    }

    /**
     * If another page rendering in progress, waits until the rendering is
     * finised. Otherwise, executes rendering immediately.
     */
    function queueRenderPage(num) {
        if (pageRendering) {
            pageNumPending = num;
        } else {
            renderPage(num);
        }
    }

    /**
     * Displays previous page.
     */
    function onPrevPage() {
        if (pageNum <= 1) {
            return;
        }
        pageNum--;
        queueRenderPage(pageNum);
    }
    document.getElementById('prev').addEventListener('click', onPrevPage);

    /**
     * Displays next page.
     */
    function onNextPage() {
        if (pageNum >= pdfDoc.numPages) {
            return;
        }
        pageNum++;
        queueRenderPage(pageNum);
    }
    document.getElementById('next').addEventListener('click', onNextPage);

    /**
     * Asynchronously downloads PDF.
     */
    // PDFJS.getDocument({
    //     url: this.docUrl,
    //     httpHeaders: { Authorization: `Bearer ${getAuthenticationToken()}` },
    //     withCredentials: true,
    // })
    pdfjsLib.getDocument({
        url: url,
        httpHeaders: { Authorization: `Bearer 71|hMW9PhsPDXjMwklIQNuMnfMQ1Bu1ji82AAfpU366` },
        // withCredentials: true,
    }).promise.then(function(pdfDoc_) {
        pdfDoc = pdfDoc_;
        document.getElementById('page_count').textContent = pdfDoc.numPages;

        // Initial/first page rendering
        renderPage(pageNum);
    });
</script>

</body>
</html>
