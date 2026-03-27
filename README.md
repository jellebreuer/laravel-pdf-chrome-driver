# Convert HTML to PDF with headless Chrome

[![Latest Version on Packagist](https://img.shields.io/packagist/v/breuer/laravel-make-pdf.svg?style=flat-square)](https://packagist.org/packages/breuer/laravel-make-pdf)
[![Total Downloads](https://img.shields.io/packagist/dt/breuer/laravel-make-pdf.svg?style=flat-square)](https://packagist.org/packages/breuer/laravel-make-pdf)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jellebreuer/laravel-make-pdf/run-tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/jellebreuer/laravel-make-pdf/actions/workflows/run-tests.yml)
[![GitHub PHPStan Action Status](https://img.shields.io/github/actions/workflow/status/jellebreuer/laravel-make-pdf/phpstan.yml?branch=master&label=phpstan&style=flat-square)](https://github.com/jellebreuer/laravel-make-pdf/actions/workflows/phpstan.yml)
[![GitHub Pint Action Status](https://img.shields.io/github/actions/workflow/status/jellebreuer/laravel-make-pdf/fix-php-code-style-issues.yml?branch=master&label=laravel%20pint&style=flat-square)](https://github.com/jellebreuer/laravel-make-pdf/actions/workflows/fix-php-code-style-issues.yml)

This package allows you to easily convert HTML to PDF using headless Chrome through Selenium, without needing Node.js.
It is inspired by Spatie's [laravel-pdf](https://github.com/spatie/laravel-pdf) package,
which uses BrowserShot and Puppeteer, but our solution offers a more PHP-centric approach using Selenium.

## Requirements

Laravel Make PDF requires **PHP 8.2+** and **Laravel 11+**.

## Installation & Setup

You can install the package via Composer:

```bash
composer require breuer/laravel-make-pdf
```

After installation, download headless Chrome using the following Artisan command:

```bash
php artisan make-pdf:install
```

To customize the package configuration, publish the configuration file:

```bash
php artisan vendor:publish --tag="laravel-make-pdf-config"
```

Here is the content of the published config file:

```php
return [
    // Configuration options will go here
];
```

## Usage

Converting HTML to PDF with this package is simple and efficient. Below are a few common use cases:

### Basic Example

Convert a Blade view to a PDF and stream it to the browser:

```php
use Breuer\MakePDF\Facades\PDF;

Route::get('/', function () {
    return PDF::view('view.name', [])->response();
});
```

Or force the browser to download the PDF file

```php
return PDF::view('view.name', [])->download();
```

### Options

#### Render Raw HTML:

Instead of passing a Blade view, you can directly pass HTML:

```php
PDF::html('<h1>Hello World</h1>')
```

#### Header and Footer

You can include a view in the header and footer of every page:

```php
PDF::view('view.name', [])
    ->headerView('view.header')
    ->footerView('view.footer')
    ->response();
```

Alternatively, set raw HTML for the header and footer:

```php
->headerHtml('<div>My header</div>')
->footerHtml('<div>My footer</div>')
```

In the header or footer, the following placeholders can be used and will be replaced with their print-specific values:

```html
<span class="date"></span>
<span class="title"></span>
<span class="pageNumber"></span>
<span class="totalPages"></span>
```

**Note:** The header and footer do not inherit the same CSS as the main content, and the default font size is 0. You should include any required CSS directly in the header/footer. Here’s an example of a styled footer view:

```html
<style>
    footer {
        font-size: 13px;
        color: black;
    }
</style>
<footer>
    <span class="date"></span>
    <span class="pageNumber"></span> / <span class="totalPages"></span>
</footer>
```

#### Landscape Orientation

Switch the page orientation to landscape:

```php
use Breuer\MakePDF\Enums\Orientation;

PDF::landscape()
```

#### Set Paper Format

Specify a standard paper format:

```php
use Breuer\MakePDF\Enums\Format;

PDF::format(Format::A4)
```

The following formats are available: `LETTER`, `LEGAL`, `A0`, `A1`, `A2`, `A3`, `A4`, `A5`, `A6`.

#### Set Custom Paper Size

Set a custom paper size, specifying height and width in inches (or another unit):

```php
use Breuer\MakePDF\Enums\Unit;

PDF::paperSize($height, $width)  // Uses inches by default
PDF::paperSize(29.7, 21, Unit::CENTIMETER)  // Uses centimeters and converts to inches
```

#### Set Margins

Set custom margins for the PDF document:

```php
use Breuer\MakePDF\Enums\Unit;

PDF::margins($top, $right, $bottom, $left) // Uses inches by default
PDF::margins(2.54, 1.27, 2.54, 1.27, Unit::CENTIMETER)  // Centimeters, converted to inches
```

#### Custom Filename

Define a custom name for the PDF when downloading from the browser.
The `.pdf` extension is automatically appended if omitted:

```php
PDF::view('view.name', [])
    ->name('custom_filename')
    ->response();
```

#### Save to File

Use the `save` method to store the PDF at a given file path:

```php
->save('/path/to/save/yourfile.pdf')
```

#### Retrieve PDF as a String

To obtain the raw PDF content as a string, use the `raw` method:

```php
$content = PDF::view('view.name', [])->raw();
```

#### Stream PDF

Display the PDF directly in the browser without saving it to disk:

```php
->response()
```

#### Force Download

Prompt the browser to immediately download the PDF:

```php
->download()
```

## License

This package is open-sourced software licensed under the MIT License.
Please see the [License File](LICENSE.md) for more information.
