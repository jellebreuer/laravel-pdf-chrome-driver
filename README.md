# Chrome DevTools Protocol driver for spatie/laravel-pdf

[![Latest Version on Packagist](https://img.shields.io/packagist/v/breuer/laravel-pdf-chrome-driver.svg?style=flat-square)](https://packagist.org/packages/breuer/laravel-pdf-chrome-driver)
[![Total Downloads](https://img.shields.io/packagist/dt/breuer/laravel-pdf-chrome-driver.svg?style=flat-square)](https://packagist.org/packages/breuer/laravel-pdf-chrome-driver)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jellebreuer/laravel-pdf-chrome-driver/run-tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/jellebreuer/laravel-pdf-chrome-driver/actions/workflows/run-tests.yml)
[![GitHub PHPStan Action Status](https://img.shields.io/github/actions/workflow/status/jellebreuer/laravel-pdf-chrome-driver/phpstan.yml?branch=master&label=phpstan&style=flat-square)](https://github.com/jellebreuer/laravel-pdf-chrome-driver/actions/workflows/phpstan.yml)
[![GitHub Pint Action Status](https://img.shields.io/github/actions/workflow/status/jellebreuer/laravel-pdf-chrome-driver/fix-php-code-style-issues.yml?branch=master&label=laravel%20pint&style=flat-square)](https://github.com/jellebreuer/laravel-pdf-chrome-driver/actions/workflows/fix-php-code-style-issues.yml)

A Chrome DevTools Protocol (CDP) driver for [spatie/laravel-pdf](https://github.com/spatie/laravel-pdf). Renders PDFs by communicating directly with `chrome-headless-shell` over CDP pipes — no Node.js, no Puppeteer, no Docker, no external services.

## Requirements

- **PHP 8.2+**
- **Laravel 11+**
- **[spatie/laravel-pdf](https://github.com/spatie/laravel-pdf) ^2.0**

## Installation

Install the package via Composer:

```bash
composer require breuer/laravel-pdf-chrome-driver
```

Download the headless Chrome binary:

```bash
php artisan pdf-chrome-driver:install
```

Set the driver in your `config/laravel-pdf.php`:

```php
'driver' => 'chrome',
```

Or via your `.env` file:

```
LARAVEL_PDF_DRIVER=chrome
```

That's it. All `spatie/laravel-pdf` functionality now uses Chrome CDP under the hood.

## Usage

This package is a driver for `spatie/laravel-pdf` — use spatie's API as documented in their [README](https://github.com/spatie/laravel-pdf):

```php
use Spatie\LaravelPdf\Facades\Pdf;

Route::get('/pdf', function () {
    return Pdf::view('invoice', ['order' => $order]);
});
```

## Configuration

Publish the config file to customize Chrome binary path or timeout:

```bash
php artisan vendor:publish --tag="pdf-chrome-driver-config"
```

```php
return [
    'path' => env('PDF_CHROME_DRIVER_CHROME_PATH'),
    'timeout' => env('PDF_CHROME_DRIVER_TIMEOUT', 10),
];
```

### Linux ARM64

Pre-built Chrome binaries are not available for Linux ARM64. Install Chromium via your package manager and point to it:

```
PDF_CHROME_DRIVER_CHROME_PATH=/usr/bin/chromium
```

## How it works

Unlike other drivers that shell out to Node.js or rely on external services, this driver:

1. Launches `chrome-headless-shell` with `--remote-debugging-pipe`
2. Communicates over CDP via file descriptors (fd 3/4) — no WebSocket, no port allocation
3. Injects HTML via `Page.setDocumentContent` — no temp files
4. Generates PDF via `Page.printToPDF`

Each request gets an isolated Chrome process with its own temp directory, cleaned up automatically.

## License

This package is open-sourced software licensed under the MIT License.
Please see the [License File](LICENSE.md) for more information.
