<?php

namespace Breuer\MakePDF\Facades;

use Breuer\MakePDF\Client;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Http\Response response()
 * @method static \Illuminate\Http\Response download()
 * @method static \Breuer\MakePDF\Client save(string $path)
 * @method static string raw()
 * @method static \Breuer\MakePDF\Client view(string $view, array $data = [])
 * @method static \Breuer\MakePDF\Client headerView(string $view, array $data = [])
 * @method static \Breuer\MakePDF\Client footerView(string $view, array $data = [])
 * @method static \Breuer\MakePDF\Client html(string $html)
 * @method static \Breuer\MakePDF\Client headerHtml(string $html)
 * @method static \Breuer\MakePDF\Client footerHtml(string $html)
 * @method static \Breuer\MakePDF\Client name(string $filename)
 * @method static \Breuer\MakePDF\Client landscape()
 * @method static \Breuer\MakePDF\Client format(\Breuer\MakePDF\Enums\Format $format)
 * @method static \Breuer\MakePDF\Client paperSize(float $height, float $width, \Breuer\MakePDF\Enums\Unit $unit = \Breuer\MakePDF\Enums\Unit::INCH)
 * @method static \Breuer\MakePDF\Client margins(float $top = 0, float $right = 0, float $bottom = 0, float $left = 0, \Breuer\MakePDF\Enums\Unit $unit = \Breuer\MakePDF\Enums\Unit::INCH)
 * @method static string chromeHeadlessBinary()
 * @method static bool onWindows()
 * @method static bool onMacARM()
 * @method static bool onMacIntel()
 * @method static bool onLinux()
 * @method static bool onLinuxARM()
 *
 * @see \Breuer\MakePDF\Client
 */
class PDF extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
