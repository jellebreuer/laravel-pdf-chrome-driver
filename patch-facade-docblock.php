<?php

use Breuer\MakePDF\Client;

require __DIR__.'/vendor/autoload.php'; // Testbench autoload

$facadeFile = __DIR__.'/src/Facades/PDF.php';
$targetClass = Client::class;

if (! file_exists($facadeFile)) {
    fwrite(STDERR, "❌ File not found: $facadeFile\n");
    exit(1);
}

if (! class_exists($targetClass)) {
    fwrite(STDERR, "❌ Target class not found: $targetClass\n");
    exit(1);
}

$content = file_get_contents($facadeFile);
$lines = explode("\n", $content);

$reflection = new ReflectionClass($targetClass);

$modified = false;

foreach ($lines as &$line) {
    if (preg_match('/@method static .* (\w+)\((.*?)\)/', $line, $matches)) {
        $methodName = $matches[1];

        if (! $reflection->hasMethod($methodName)) {
            continue;
        }

        $method = $reflection->getMethod($methodName);
        $params = $method->getParameters();

        foreach ($params as $param) {
            if (
                ! $param->isDefaultValueAvailable() ||
                ! str_contains($line, $param->getName().' = unknown')
            ) {
                continue;
            }

            $default = $param->getDefaultValue();

            if (is_object($default)) {
                $class = get_class($default);

                if (enum_exists($class)) {

                    // Build replacement
                    $replacement = '\\'.$class.'::'.$default->name;
                    $pattern = '/(\\'.preg_quote($class, '/').'\s+\$'.$param->getName().' = )unknown/';
                    $line = preg_replace($pattern, '$1'.$replacement, $line);
                    $modified = true;
                }
            }
        }
    }
}

if ($modified) {
    file_put_contents($facadeFile, implode("\n", $lines));
    echo "✅ Facade docblock patched with enum defaults.\n";
} else {
    echo "ℹ️ No changes made. Everything is already up to date.\n";
}
