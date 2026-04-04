<?php

namespace Breuer\MakePDF;

class Platform
{
    public static function chromeHeadlessBinary(): string
    {
        $config_path = config('make-pdf.chrome_path');
        if (is_string($config_path) && $config_path !== '') {
            return $config_path;
        }

        if (self::onLinuxARM()) {
            throw new \RuntimeException(
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

        throw new \RuntimeException('Platform not supported');
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
