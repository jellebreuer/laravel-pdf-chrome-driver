<?php

// config for Breuer/PdfChromeDriver
return [

    /*
    |--------------------------------------------------------------------------
    | Custom Binary Paths
    |--------------------------------------------------------------------------
    |
    | By default, this package uses bundled Chrome binaries that are downloaded
    | via `php artisan pdf-chrome-driver:install`. However, you can specify
    | custom paths to use system-installed binaries instead.
    |
    | This is particularly useful for:
    | - Linux ARM64 environments (e.g., Docker on Apple Silicon)
    | - Environments where you prefer to use system-managed Chromium
    |
    | Then configure the path:
    |   'path' => '/usr/bin/chromium',
    |
    */

    'path' => env('PDF_CHROME_DRIVER_CHROME_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum number of seconds allowed for the entire PDF generation process,
    | including launching Chrome and all CDP communication. If exceeded, Chrome
    | is killed and a RuntimeException is thrown.
    |
    */

    'timeout' => env('PDF_CHROME_DRIVER_TIMEOUT', 10),

];
