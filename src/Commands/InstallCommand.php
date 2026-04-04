<?php

namespace Breuer\ChromeDriver\Commands;

use Breuer\ChromeDriver\Platform;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use ZipArchive;

use function Breuer\ChromeDriver\package_path;

class InstallCommand extends Command
{
    public $signature = 'pdf-chrome-driver:install';

    public $description = 'Download latest stable chrome-headless-shell';

    public function handle(): int
    {
        if (Platform::onWindows()) {
            $this->error('Windows is not supported. This package uses CDP pipes which require a Unix-based OS.');

            return self::FAILURE;
        }

        if (Platform::onLinuxARM()) {
            $this->warn('Linux ARM64 detected.');
            $this->warn('Pre-built Chrome binaries are not available for this platform.');
            $this->newLine();
            $this->info('To use this package on Linux ARM64, install Chromium via your package manager or some other route.');
            $this->newLine();
            $this->info('Then configure the path in your .env file:');
            $this->line('  LARAVEL_PDF_CHROME_PATH=/usr/bin/chromium');

            return self::SUCCESS;
        }

        if (! File::exists(package_path('browser'))) {
            $this->info('Creating directory: '.package_path('browser'));
            File::ensureDirectoryExists(package_path('browser'));
        } else {
            $this->info('Removing old browser installations');
            File::deleteDirectory(package_path('browser'), true);
        }

        $this->info('Fetching latest chrome build information');
        $response = Http::get('https://googlechromelabs.github.io/chrome-for-testing/last-known-good-versions-with-downloads.json');
        $headless_chrome_downloads = $this->findHeadlessChromeDownloadsInResponse($response);

        foreach ($headless_chrome_downloads as $download) {
            if ($download->platform !== $this->getPlatformKey()) {
                continue;
            }

            $this->info('Downloading latest stable headless chrome');
            $zipfile = package_path('browser/chrome-headless-shell.zip');
            Http::sink($zipfile)->get($download->url);

            $this->info('Unzipping');
            $zip = new ZipArchive;
            $zip->open($zipfile);
            $zip->extractTo(package_path('browser'));
            $zip->close();

            File::delete($zipfile);

            break;
        }

        $this->info('Fixing permissions');
        chmod(Platform::chromeHeadlessBinary(), 0755);

        $this->info('Installation complete');

        return self::SUCCESS;
    }

    /**
     * @return array<int, object{platform: string, url: string}>
     */
    protected function findHeadlessChromeDownloadsInResponse(Response $response): array
    {
        return $this->findDownloadsInResponse($response, 'chrome-headless-shell');
    }

    /**
     * @return array<int, object{platform: string, url: string}>
     *
     * @throws \Exception
     */
    protected function findDownloadsInResponse(Response $response, string $downloadKey): array
    {
        if (! $response->ok()) {
            throw new \Exception('Problem connecting to googlechromelabs.com');
        }

        $object = $response->object();
        if (
            ! is_object($object)
            || ! isset($object->channels)
            || ! is_object($object->channels)
            || ! isset($object->channels->Stable)
            || ! is_object($object->channels->Stable)
            || ! isset($object->channels->Stable->downloads)
            || ! is_object($object->channels->Stable->downloads)
            || ! isset($object->channels->Stable->downloads->{$downloadKey})
            || ! is_array($object->channels->Stable->downloads->{$downloadKey})
        ) {
            throw new \Exception('Problem parsing response from googlechromelabs.com');
        }

        $downloads = $object->channels->Stable->downloads->{$downloadKey};

        $result = [];
        foreach ($downloads as $download) {
            if (
                is_object($download)
                && isset($download->platform)
                && is_string($download->platform)
                && isset($download->url)
                && is_string($download->url)
            ) {
                $result[] = (object) [
                    'platform' => $download->platform,
                    'url' => $download->url,
                ];
            } else {
                throw new \Exception("Invalid {$downloadKey} download entry");
            }
        }

        return $result;
    }

    protected function getPlatformKey(): string
    {

        if (Platform::onLinux()) {
            return 'linux64';
        } elseif (Platform::onMacARM()) {
            return 'mac-arm64';
        } elseif (Platform::onMacIntel()) {
            return 'mac-x64';
        }

        throw new \Exception('Platform not supported');
    }
}
