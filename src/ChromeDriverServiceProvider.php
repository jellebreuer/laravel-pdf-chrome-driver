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
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-pdf-chrome-driver')
            ->hasConfigFile('pdf-chrome-driver')
            ->hasCommand(InstallCommand::class);
    }

    public function registeringPackage(): void
    {
        if (! interface_exists(PdfDriver::class)) {
            return;
        }

        $this->app->singleton('laravel-pdf.driver.chrome', function () {
            /** @var array<string, mixed> $config */
            $config = config('pdf-chrome-driver', []);

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
