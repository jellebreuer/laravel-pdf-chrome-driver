<?php

namespace Breuer\MakePDF;

use Breuer\MakePDF\Commands\InstallCommand;
use Breuer\MakePDF\Drivers\MakePdfDriver;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPdf\Drivers\PdfDriver;
use Spatie\LaravelPdf\Exceptions\InvalidDriver;

class PDFServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-make-pdf')
            ->hasConfigFile()
            ->hasCommand(InstallCommand::class);
    }

    public function registeringPackage(): void
    {
        if (! interface_exists(PdfDriver::class)) {
            return;
        }

        $this->app->singleton('laravel-pdf.driver.make-pdf', function () {
            /** @var array<string, mixed> $config */
            $config = config('make-pdf', []);

            return new MakePdfDriver($config);
        });
    }

    public function bootingPackage(): void
    {
        if (! interface_exists(PdfDriver::class)) {
            return;
        }

        $this->app->singleton(PdfDriver::class, function () {
            /** @var string $driverName */
            $driverName = config('laravel-pdf.driver', 'browsershot');

            return match ($driverName) {
                'make-pdf' => app('laravel-pdf.driver.make-pdf'),
                'browsershot' => app('laravel-pdf.driver.browsershot'),
                'cloudflare' => app('laravel-pdf.driver.cloudflare'),
                'dompdf' => app('laravel-pdf.driver.dompdf'),
                'gotenberg' => app('laravel-pdf.driver.gotenberg'),
                'weasyprint' => app('laravel-pdf.driver.weasyprint'),
                default => throw InvalidDriver::unknown($driverName),
            };
        });
    }
}
