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
    | Then configure the path:
    |   'chrome_path' => '/usr/bin/chromium',
    |
    */

    'chrome_path' => env('MAKE_PDF_CHROME_PATH'),

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

    'timeout' => env('MAKE_PDF_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Temporary Directory
    |--------------------------------------------------------------------------
    |
    | Directory where Chrome's temporary user-data-dir folders are created.
    | Each PDF generation creates a unique subdirectory here, which is cleaned
    | up automatically after Chrome exits.
    |
    | Defaults to storage/make-pdf.
    |
    | Warning: You can use /tmp for better performance, but beware of
    | PrivateTmp=true in systemd services (e.g. php-fpm, nginx). When enabled,
    | each service gets its own isolated /tmp, so Chrome (spawned by PHP) and
    | the cleanup process may see different /tmp directories, causing orphaned
    | files that never get cleaned up.
    |
    */

    'temp_path' => env('MAKE_PDF_TEMP_PATH'),

];
