<?php

return [

    /*
     * Path to the Chrome/Chromium binary.
     *
     * By default, this package uses bundled Chrome binaries downloaded
     * via `php artisan pdf-chrome-driver:install`. Set this to use a
     * system-installed binary instead (e.g. on Linux ARM64).
     */
    'path' => env('LARAVEL_PDF_CHROME_PATH'),

    /*
     * Maximum number of seconds allowed for PDF generation,
     * including launching Chrome and all CDP communication.
     */
    'timeout' => env('LARAVEL_PDF_CHROME_TIMEOUT', 10),

];
