<?php

namespace Breuer\ChromeDriver\Commands;

use Breuer\ChromeDriver\Platform;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use ZipArchive;

use function Breuer\ChromeDriver\package_path;

class InstallCommand extends Command
{
    public $signature = 'pdf-chrome-driver:install
                    {version? : Chrome version to install (e.g. 137, 137.0.7151.55, or "latest")}
                    {--path= : Directory to install into (absolute or relative to project root, e.g. "storage/chrome")}';

    public $description = 'Download chrome-headless-shell';

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

        $installDir = $this->installDirectory();

        if (! File::exists($installDir)) {
            $this->info("Creating directory: {$installDir}");
            File::ensureDirectoryExists($installDir);
        } else {
            $this->info('Removing old browser installations');
            File::deleteDirectory($installDir, true);
        }

        $version = $this->resolveVersion();

        $this->info("Resolved version: {$version}");

        $downloadUrl = $this->resolveDownloadUrl($version);

        $this->info('Downloading chrome-headless-shell');
        $zipfile = $installDir.'/chrome-headless-shell.zip';
        $this->downloadWithProgress($downloadUrl, $zipfile);

        $this->info('Extracting binary');
        $this->extractBinary($zipfile, $installDir);

        $binaryPath = $this->findBinary($installDir);
        $this->info('Fixing permissions');
        chmod($binaryPath, 0755);

        if ($this->option('path')) {
            $this->newLine();
            $this->info('Installed to custom directory. Configure the binary path in your .env:');
            $this->line("  LARAVEL_PDF_CHROME_PATH={$binaryPath}");
        }

        $this->info('Installation complete');

        return self::SUCCESS;
    }

    protected function installDirectory(): string
    {
        $path = $this->option('path');

        if (is_string($path) && $path !== '') {
            if (! str_starts_with($path, '/')) {
                $path = base_path($path);
            }

            return rtrim($path, '/');
        }

        return package_path('browser');
    }

    protected function findBinary(string $directory): string
    {
        $platformDir = 'chrome-headless-shell-'.$this->getPlatformKey();
        $binaryPath = $directory.'/'.$platformDir.'/chrome-headless-shell';

        if (file_exists($binaryPath)) {
            return $binaryPath;
        }

        throw new \Exception("Could not find chrome-headless-shell binary at {$binaryPath}.");
    }

    protected function resolveVersion(): string
    {
        $version = $this->argument('version');

        if (! $version || $version === 'latest') {
            return $this->latestStableVersion();
        }

        if (! ctype_digit((string) $version)) {
            return $version;
        }

        return $this->resolveVersionFromMilestone((int) $version);
    }

    protected function latestStableVersion(): string
    {
        $this->info('Fetching latest stable chrome build information');

        $response = Http::get('https://googlechromelabs.github.io/chrome-for-testing/last-known-good-versions-with-downloads.json');

        if (! $response->ok()) {
            throw new \Exception('Problem connecting to googlechromelabs.com');
        }

        /** @var array{channels: array{Stable: array{version: string}}} $data */
        $data = $response->json();

        return $data['channels']['Stable']['version'];
    }

    protected function resolveVersionFromMilestone(int $milestone): string
    {
        $this->info("Fetching version for milestone {$milestone}");

        $milestones = $this->fetchMilestonesData();

        $milestone_data = $milestones[$milestone] ?? null;

        if (! is_array($milestone_data) || ! isset($milestone_data['version']) || ! is_string($milestone_data['version'])) {
            throw new \Exception("Could not resolve version for milestone {$milestone}.");
        }

        return $milestone_data['version'];
    }

    protected function resolveDownloadUrl(string $version): string
    {
        $milestone = (int) $version;
        $milestones = $this->fetchMilestonesData();
        $platformKey = $this->getPlatformKey();

        $milestoneData = $milestones[$milestone] ?? null;

        if (! is_array($milestoneData)
            || ! isset($milestoneData['downloads'])
            || ! is_array($milestoneData['downloads'])
            || ! isset($milestoneData['downloads']['chrome-headless-shell'])
            || ! is_array($milestoneData['downloads']['chrome-headless-shell'])) {
            throw new \Exception("No chrome-headless-shell downloads found for version {$version}.");
        }

        /** @var array<int, array{platform: string, url: string}> $downloads */
        $downloads = $milestoneData['downloads']['chrome-headless-shell'];

        foreach ($downloads as $download) {
            if ($download['platform'] === $platformKey) {
                return $download['url'];
            }
        }

        throw new \Exception("No chrome-headless-shell download found for platform {$platformKey}.");
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function fetchMilestonesData(): array
    {
        $response = Http::get('https://googlechromelabs.github.io/chrome-for-testing/latest-versions-per-milestone-with-downloads.json');

        if (! $response->ok()) {
            throw new \Exception('Problem connecting to googlechromelabs.com');
        }

        /** @var array{milestones: array<int|string, mixed>} $data */
        $data = $response->json();

        return $data['milestones'];
    }

    protected function downloadWithProgress(string $url, string $destination): void
    {
        $progressBar = $this->output->createProgressBar();
        $progressBar->setFormat(' %current_size% / %total_size% [%bar%] %percent:3s%%');
        $progressBar->setMessage('0 KB', 'current_size');
        $progressBar->setMessage('? MB', 'total_size');

        Http::withOptions([
            'progress' => function (int $totalDownload, int $downloaded) use ($progressBar): void {
                if ($totalDownload > 0) {
                    $progressBar->setMaxSteps($totalDownload);
                    $progressBar->setMessage($this->formatBytes($totalDownload), 'total_size');
                }

                if ($downloaded > 0) {
                    $progressBar->setProgress($downloaded);
                    $progressBar->setMessage($this->formatBytes($downloaded), 'current_size');
                }
            },
        ])->sink($destination)->get($url);

        $progressBar->finish();
        $this->newLine();
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        return round($bytes / 1024, 1).' KB';
    }

    /**
     * @throws \Exception
     */
    protected function extractBinary(string $zipfile, string $destination): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipfile) !== true) {
            throw new \Exception('Could not open the downloaded zip archive.');
        }

        $zip->extractTo($destination);
        $zip->close();
        File::delete($zipfile);
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
