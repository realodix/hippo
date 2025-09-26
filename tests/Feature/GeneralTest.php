<?php

namespace Realodix\Hippo\Test\Feature;

use Realodix\Hippo\Test\TestCase;

class GeneralTest extends TestCase
{
    public function testComparesFiles(): void
    {
        $inputFile = __DIR__.'/../Integration/general_actual.txt';
        $expectedFile = __DIR__.'/../Integration/general_expected.txt';

        $this->assertFilter($expectedFile, $inputFile);
    }
}
