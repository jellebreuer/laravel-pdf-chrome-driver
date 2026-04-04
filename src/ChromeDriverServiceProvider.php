<?php

namespace Breuer\ChromeDriver;

use Breuer\ChromeDriver\Commands\InstallCommand;
use Breuer\ChromeDriver\Drivers\ChromeDriver;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPdf\Drivers\PdfDriver;
use Spatie\LaravelPdf\Exceptions\InvalidDriver;

class ChromeDriverServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-pdf-chrome-driver')
            ->hasCommand(InstallCommand::class);
    }

    public function registeringPackage(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/chrome.php', 'laravel-pdf.chrome');

        if (! interface_exists(PdfDriver::class)) {
            return;
        }

        $this->app->singleton('laravel-pdf.driver.chrome', function () {
            /** @var array<string, mixed> $config */
            $config = config('laravel-pdf.chrome', []);

            return new ChromeDriver($config);
        });
    }

    public function bootingPackage(): void
    {
        if (! interface_exists(PdfDriver::class)) {
            return;
        }

        $this->app->singleton(PdfDriver::class, function () {
            /** @var string $driver_name */
            $driver_name = config('laravel-pdf.driver', 'browsershot');

            return match ($driver_name) {
                'chrome' => app('laravel-pdf.driver.chrome'),
                'browsershot' => app('laravel-pdf.driver.browsershot'),
                'cloudflare' => app('laravel-pdf.driver.cloudflare'),
                'dompdf' => app('laravel-pdf.driver.dompdf'),
                'gotenberg' => app('laravel-pdf.driver.gotenberg'),
                'weasyprint' => app('laravel-pdf.driver.weasyprint'),
                default => throw InvalidDriver::unknown($driver_name),
            };
        });
    }
}
