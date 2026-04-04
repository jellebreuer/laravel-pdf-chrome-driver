<?php

namespace Breuer\ChromeDriver;

use function Illuminate\Filesystem\join_paths;

if (! function_exists('Breuer\ChromeDriver\package_path')) {
    function package_path(string $path = '', string ...$paths): string
    {
        return join_paths(dirname(__FILE__, 2), $path, ...$paths);
    }
}
