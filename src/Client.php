<?php

namespace Breuer\MakePDF;

use Breuer\MakePDF\Enums\Format;
use Breuer\MakePDF\Enums\Orientation;
use Breuer\MakePDF\Enums\Unit;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Client
{
    protected ?ChromeProcess $chrome = null;

    protected string $filename = 'download.pdf';

    protected Orientation $orientation = Orientation::PORTRAIT;

    protected float $paperWidth = 8.27;

    protected float $paperHeight = 11.69;

    protected float $marginTop = 0;

    protected float $marginBottom = 0;

    protected float $marginLeft = 0;

    protected float $marginRight = 0;

    protected string $html = '';

    protected string $footerHtml = '';

    protected string $headerHtml = '';

    protected string $viewName = '';

    protected string $headerViewName = '';

    protected string $footerViewName = '';

    /** @var array<mixed> */
    protected array $viewData = [];

    /** @var array<mixed> */
    protected array $headerViewData = [];

    /** @var array<mixed> */
    protected array $footerViewData = [];

    private const DATA_DIR_PREFIX = 'user-data-dir-';

    public function response(): Response
    {
        return response($this->getContent(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->filename.'"',
        ]);
    }

    public function download(): Response
    {
        return response($this->getContent(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename.'"',
        ]);
    }

    public function save(string $path): self
    {
        File::put($path, $this->getContent());

        return $this;
    }

    public function raw(): string
    {
        return $this->getContent();
    }

    /** @param array<mixed> $data */
    public function view(string $view, array $data = []): self
    {
        $this->viewName = $view;

        $this->viewData = $data;

        return $this;
    }

    /** @param array<mixed> $data */
    public function headerView(string $view, array $data = []): self
    {
        $this->headerViewName = $view;

        $this->headerViewData = $data;

        return $this;
    }

    /** @param array<mixed> $data */
    public function footerView(string $view, array $data = []): self
    {
        $this->footerViewName = $view;

        $this->footerViewData = $data;

        return $this;
    }

    public function html(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    public function headerHtml(string $html): self
    {
        $this->headerHtml = $html;

        return $this;
    }

    public function footerHtml(string $html): self
    {
        $this->footerHtml = $html;

        return $this;
    }

    public function name(string $filename): self
    {
        $this->filename = Str::finish($filename, '.pdf');

        return $this;
    }

    public function landscape(): self
    {
        $this->orientation = Orientation::LANDSCAPE;

        return $this;
    }

    public function format(Format $format): self
    {
        $this->paperHeight = $format->heightInInches();
        $this->paperWidth = $format->widthInInches();

        return $this;
    }

    public function paperSize(float $height, float $width, Unit $unit = Unit::INCH): self
    {
        $this->paperHeight = $unit->toInches($height);
        $this->paperWidth = $unit->toInches($width);

        return $this;
    }

    public function margins(float $top = 0, float $right = 0, float $bottom = 0, float $left = 0, Unit $unit = Unit::INCH): self
    {
        $this->marginTop = $unit->toInches($top);
        $this->marginBottom = $unit->toInches($bottom);
        $this->marginLeft = $unit->toInches($left);
        $this->marginRight = $unit->toInches($right);

        return $this;
    }

    protected function getContent(): string
    {
        if ($this->viewName) {
            $this->html = $this->renderView($this->viewName, $this->viewData);
        }

        if ($this->headerViewName) {
            $this->headerHtml = $this->renderView($this->headerViewName, $this->headerViewData);
        }

        if ($this->footerViewName) {
            $this->footerHtml = $this->renderView($this->footerViewName, $this->footerViewData);
        }

        try {
            $this->startBrowser();
            /** @var int|float $timeout */
            $timeout = config('make-pdf.timeout', 30);
            $this->chrome->setTimeout((float) $timeout);

            /** @var array{result: array{targetId: string}} $target */
            $target = $this->chrome->send('Target.createTarget', ['url' => 'about:blank']);

            /** @var array{result: array{sessionId: string}} $session */
            $session = $this->chrome->send('Target.attachToTarget', [
                'targetId' => $target['result']['targetId'],
                'flatten' => true,
            ]);
            $sessionId = $session['result']['sessionId'];

            $this->chrome->send('Page.enable', [], $sessionId);

            /** @var array{result: array{frameTree: array{frame: array{id: string}}}} $frameTree */
            $frameTree = $this->chrome->send('Page.getFrameTree', [], $sessionId);

            $this->chrome->send('Page.setDocumentContent', [
                'frameId' => $frameTree['result']['frameTree']['frame']['id'],
                'html' => $this->html,
            ], $sessionId);

            $displayHeaderFooter = ! empty($this->footerHtml) || ! empty($this->headerHtml);

            /** @var array{result: array{data: string}} $response */
            $response = $this->chrome->send('Page.printToPDF', [
                'landscape' => $this->orientation === Orientation::LANDSCAPE,
                'printBackground' => true,
                'displayHeaderFooter' => $displayHeaderFooter,
                'headerTemplate' => $this->headerHtml,
                'footerTemplate' => $this->footerHtml,
                'paperWidth' => $this->paperWidth,
                'paperHeight' => $this->paperHeight,
                'marginTop' => $this->marginTop,
                'marginBottom' => $this->marginBottom,
                'marginLeft' => $this->marginLeft,
                'marginRight' => $this->marginRight,
            ], $sessionId);
        } finally {
            $this->stopBrowser();
        }

        return base64_decode($response['result']['data']);
    }

    /**
     * @param  array<mixed>  $data
     */
    protected function renderView(string $name, array $data): string
    {
        if (! view()->exists($name)) {
            return '';
        }

        $viewData = [];
        foreach ($data as $key => $value) {
            $viewData[(string) $key] = $value;
        }

        return view($name, $viewData)->render();
    }

    /**
     * @phpstan-assert !null $this->chrome
     */
    protected function startBrowser(): void
    {
        if (self::onWindows()) {
            throw new \Exception('Windows is not supported. This package uses CDP pipes which require a Unix-based OS.');
        }

        $chromeBinary = self::chromeHeadlessBinary();

        if (! File::exists($chromeBinary)) {
            throw new \Exception('Chrome binary not found, please run: php artisan make-pdf:install');
        }

        $baseDir = self::makePdfTmpDir();

        self::cleanupStaleFiles($baseDir);

        $userDataDir = $baseDir.'/'.self::DATA_DIR_PREFIX.Str::random(16);
        File::makeDirectory($userDataDir);

        $this->chrome = new ChromeProcess;
        $this->chrome->start([
            $chromeBinary,
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
            '--user-data-dir='.$userDataDir,
            '--remote-debugging-pipe',
        ], $this->chromeEnvironment());

        $this->chrome->onExit(function () use ($userDataDir): void {
            if (is_dir($userDataDir)) {
                self::deleteDirectory($userDataDir);
            }
        });
    }

    protected static function makePdfTmpDir(): string
    {
        $tmp = sys_get_temp_dir().'/laravel-make-pdf';
        File::ensureDirectoryExists($tmp);

        return $tmp;
    }

    /** @return array<string, string> */
    protected function chromeEnvironment(): array
    {
        $env = ['TMPDIR' => self::makePdfTmpDir()];

        if (self::onLinux() || self::onLinuxARM()) {
            $display = $_ENV['DISPLAY'] ?? ':0';
            $env['DISPLAY'] = is_string($display) ? $display : ':0';
        }

        return $env;
    }

    /**
     * Remove stale user-data-dir folders and Chromium singleton files.
     * Only deletes entries older than double the configured timeout to avoid
     * interfering with parallel processes.
     */
    protected static function cleanupStaleFiles(string $baseDir): void
    {
        /** @var int|float $timeout */
        $timeout = config('make-pdf.timeout', 30);
        $threshold = time() - ((int) $timeout * 2);

        /** @var list<string> $paths */
        $paths = array_merge(
            File::glob($baseDir.'/'.self::DATA_DIR_PREFIX.'*'),
            File::glob($baseDir.'/.org.chromium.Chromium.*'),
            File::glob($baseDir.'/org.chromium.Chromium.*'),
            File::glob($baseDir.'/.com.google.Chrome.*'),
        );

        foreach ($paths as $path) {
            if (File::lastModified($path) > $threshold) {
                continue;
            }

            File::isDirectory($path) ? File::deleteDirectory($path) : File::delete($path);
        }
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

    protected function stopBrowser(): void
    {
        if ($this->chrome === null) {
            return;
        }

        $this->chrome->stop();
        $this->chrome = null;
    }

    public function __destruct()
    {
        $this->stopBrowser();
    }

    public static function chromeHeadlessBinary(): string
    {
        $configPath = config('make-pdf.chrome_path');
        if (is_string($configPath) && $configPath !== '') {
            return $configPath;
        }

        if (self::onLinuxARM()) {
            throw new \Exception(
                'Linux ARM64 detected. Pre-built Chrome binaries are not available for this platform. '.
                'Please install Chromium via your package manager or some other route '.
                'and configure the binary paths in config/make-pdf.php or via environment variables: '.
                'MAKE_PDF_CHROME_PATH'
            );
        }

        if (self::onLinux()) {
            return package_path('browser', 'chrome-headless-shell-linux64', 'chrome-headless-shell');
        } elseif (self::onMacARM()) {
            return package_path('browser', 'chrome-headless-shell-mac-arm64', 'chrome-headless-shell');
        } elseif (self::onMacIntel()) {
            return package_path('browser', 'chrome-headless-shell-mac-x64', 'chrome-headless-shell');
        }

        throw new \Exception('Platform not supported');
    }

    public static function onWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    public static function onMacARM(): bool
    {
        return PHP_OS_FAMILY === 'Darwin' && php_uname('m') === 'arm64';
    }

    public static function onMacIntel(): bool
    {
        return PHP_OS_FAMILY === 'Darwin' && php_uname('m') !== 'arm64';
    }

    public static function onLinux(): bool
    {
        return PHP_OS_FAMILY === 'Linux' && ! self::onLinuxARM();
    }

    public static function onLinuxARM(): bool
    {
        return PHP_OS_FAMILY === 'Linux' && in_array(php_uname('m'), ['aarch64', 'arm64']);
    }
}
