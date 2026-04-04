<?php

namespace Breuer\ChromeDriver\Drivers;

use Breuer\ChromeDriver\ChromeProcess;
use Breuer\ChromeDriver\Platform;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Drivers\PdfDriver;
use Spatie\LaravelPdf\Enums\Orientation;
use Spatie\LaravelPdf\PdfOptions;

class ChromeDriver implements PdfDriver
{
    private const RUN_DIR_PREFIX = 'laravel-pdf-chrome-driver-';

    /** @param array<string, mixed> $config */
    public function __construct(protected array $config = []) {}

    public function generatePdf(string $html, ?string $headerHtml, ?string $footerHtml, PdfOptions $options): string
    {
        return $this->render($html, $headerHtml, $footerHtml, $options);
    }

    public function savePdf(string $html, ?string $headerHtml, ?string $footerHtml, PdfOptions $options, string $path): void
    {
        file_put_contents($path, $this->render($html, $headerHtml, $footerHtml, $options));
    }

    protected function render(string $html, ?string $headerHtml, ?string $footerHtml, PdfOptions $options): string
    {
        $paper_width = 8.27;
        $paper_height = 11.69;
        $margin_top = 0.0;
        $margin_right = 0.0;
        $margin_bottom = 0.0;
        $margin_left = 0.0;
        $landscape = false;

        if ($options->format) {
            [$paper_width, $paper_height] = $this->formatToInches($options->format);
        }

        if ($options->paperSize) {
            /** @var array{width: float, height: float, unit: string} $paper_size */
            $paper_size = $options->paperSize;
            $paper_width = $this->toInches($paper_size['width'], $paper_size['unit']);
            $paper_height = $this->toInches($paper_size['height'], $paper_size['unit']);
        }

        if ($options->margins) {
            /** @var array{top: float, right: float, bottom: float, left: float, unit: string} $margins */
            $margins = $options->margins;
            $margin_top = $this->toInches($margins['top'], $margins['unit']);
            $margin_right = $this->toInches($margins['right'], $margins['unit']);
            $margin_bottom = $this->toInches($margins['bottom'], $margins['unit']);
            $margin_left = $this->toInches($margins['left'], $margins['unit']);
        }

        if ($options->orientation === Orientation::Landscape->value) {
            $landscape = true;
        }

        $display_header_footer = $headerHtml !== null || $footerHtml !== null;

        $chrome = null;

        try {
            $chrome = $this->startBrowser();

            /** @var int|float $timeout */
            $timeout = $this->config['timeout'] ?? config('pdf-chrome-driver.timeout', 10);
            $chrome->setTimeout((float) $timeout);

            /** @var array{result: array{targetId: string}} $target */
            $target = $chrome->send('Target.createTarget', ['url' => 'about:blank']);

            /** @var array{result: array{sessionId: string}} $session */
            $session = $chrome->send('Target.attachToTarget', [
                'targetId' => $target['result']['targetId'],
                'flatten' => true,
            ]);
            $session_id = $session['result']['sessionId'];

            $chrome->send('Page.enable', [], $session_id);

            /** @var array{result: array{frameTree: array{frame: array{id: string}}}} $frame_tree */
            $frame_tree = $chrome->send('Page.getFrameTree', [], $session_id);

            $chrome->send('Page.setDocumentContent', [
                'frameId' => $frame_tree['result']['frameTree']['frame']['id'],
                'html' => $html,
            ], $session_id);

            $print_params = [
                'landscape' => $landscape,
                'printBackground' => true,
                'displayHeaderFooter' => $display_header_footer,
                'headerTemplate' => $headerHtml ?? '',
                'footerTemplate' => $footerHtml ?? '',
                'paperWidth' => $paper_width,
                'paperHeight' => $paper_height,
                'marginTop' => $margin_top,
                'marginBottom' => $margin_bottom,
                'marginLeft' => $margin_left,
                'marginRight' => $margin_right,
            ];

            if ($options->scale !== null) {
                $print_params['scale'] = $options->scale;
            }

            if ($options->pageRanges !== null) {
                $print_params['pageRanges'] = $options->pageRanges;
            }

            if ($options->tagged) {
                $print_params['generateTaggedPDF'] = true;
            }

            /** @var array{result: array{data: string}} $response */
            $response = $chrome->send('Page.printToPDF', $print_params, $session_id);
        } finally {
            $chrome?->stop();
        }

        return base64_decode($response['result']['data']);
    }

    protected function startBrowser(): ChromeProcess
    {
        if (Platform::onWindows()) {
            throw new \RuntimeException('Windows is not supported. This package uses CDP pipes which require a Unix-based OS.');
        }

        $chrome_binary = Platform::chromeHeadlessBinary();

        if (! File::exists($chrome_binary)) {
            throw new \RuntimeException('Chrome binary not found, please run: php artisan pdf-chrome-driver:install');
        }

        $run_dir = sys_get_temp_dir().'/'.self::RUN_DIR_PREFIX.Str::random(16);
        $user_data_dir = $run_dir.'/user-data-dir';
        File::makeDirectory($user_data_dir, 0755, true);

        $chrome = new ChromeProcess;
        $chrome->start([
            $chrome_binary,
            '--headless',
            '--no-first-run',
            '--no-sandbox',
            '--no-zygote',
            '--disable-background-networking',
            '--disable-background-timer-throttling',
            '--disable-backgrounding-occluded-windows',
            '--disable-breakpad',
            '--disable-client-side-phishing-detection',
            '--disable-default-apps',
            '--disable-dev-shm-usage',
            '--disable-extensions',
            '--disable-hang-monitor',
            '--disable-ipc-flooding-protection',
            '--disable-renderer-backgrounding',
            '--disable-sync',
            '--disable-features=Translate',
            '--font-render-hinting=none',
            '--force-color-profile=srgb',
            '--ignore-certificate-errors',
            '--safebrowsing-disable-auto-update',
            '--user-data-dir='.$user_data_dir,
            '--remote-debugging-pipe',
        ], $this->chromeEnvironment($run_dir));

        $chrome->onExit(function () use ($run_dir): void {
            if (is_dir($run_dir)) {
                self::deleteDirectory($run_dir);
            }
        });

        return $chrome;
    }

    /** @return array<string, string> */
    protected function chromeEnvironment(string $tmp_dir): array
    {
        $env = ['TMPDIR' => $tmp_dir];

        if (Platform::onLinux() || Platform::onLinuxARM()) {
            $display = $_ENV['DISPLAY'] ?? ':0';
            $env['DISPLAY'] = is_string($display) ? $display : ':0';
        }

        return $env;
    }

    protected function toInches(float $value, string $unit): float
    {
        return match ($unit) {
            'in' => round($value, 2),
            'cm' => round($value * 0.3937007874, 2),
            'mm' => round($value * 0.03937007874, 2),
            'px' => round($value / 96, 2),
            default => round($value * 0.03937007874, 2),
        };
    }

    /** @return array{float, float} */
    protected function formatToInches(string $format): array
    {
        return match (strtolower($format)) {
            'letter' => [8.5, 11],
            'legal' => [8.5, 14],
            'tabloid' => [11, 17],
            'ledger' => [17, 11],
            'a0' => [33.11, 46.81],
            'a1' => [23.39, 33.11],
            'a2' => [16.54, 23.39],
            'a3' => [11.69, 16.54],
            'a4' => [8.27, 11.69],
            'a5' => [5.83, 8.27],
            'a6' => [4.13, 5.83],
            default => [8.27, 11.69],
        };
    }

    protected static function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        /** @var \SplFileInfo $item */
        foreach (new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS) as $item) {
            $item->isDir() && ! $item->isLink()
                ? self::deleteDirectory($item->getPathname())
                : @unlink($item->getPathname());
        }

        @rmdir($directory);
    }
}
