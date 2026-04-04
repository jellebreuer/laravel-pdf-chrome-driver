<?php

use Breuer\ChromeDriver\Tests\TestCase;
use Illuminate\Testing\TestResponse;

uses(TestCase::class)->in(__DIR__);

expect()->pipe('toMatchSnapshot', function (Closure $next) {
    if ($this->value instanceof TestResponse) {
        $this->value = $this->value->getContent();
        $this->value = preg_replace('/^\/CreationDate.+$/m', '', $this->value);
        $this->value = preg_replace('/^\/ModDate.+$/m', '', $this->value);
    }

    return $next();
});
