<?php

// config for Breuer/MakePDF
return [

    /*
    |--------------------------------------------------------------------------
    | Custom Binary Paths
    |--------------------------------------------------------------------------
    |
    | By default, this package uses bundled Chrome binaries that are downloaded
    | via `php artisan make-pdf:install`. However, you can specify custom paths
    | to use system-installed binaries instead.
    |
    | This is particularly useful for:
    | - Linux ARM64 environments (e.g., Docker on Apple Silicon)
    | - Environments where you prefer to use system-managed Chromium
    |
    | For Linux ARM64, install Chromium via your package manager:
    |   apt-get install chromium chromium-driver
    |
    | Then configure the paths:
    |   'chrome_path' => '/usr/bin/chromium',
    |   'chromedriver_path' => '/usr/bin/chromedriver',
    |
    */

    'chrome_path' => env('MAKE_PDF_CHROME_PATH'),

    'chromedriver_path' => env('MAKE_PDF_CHROMEDRIVER_PATH'),

];
