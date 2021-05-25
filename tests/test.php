<?php

use PHPUnit\Framework\TestCase;
use \Appkita\SPARK\Serve;
final class ServeTest extends TestCase
{
    public function testServe() {
        $serve = new Serve();
        $serve->run();
    }
}

