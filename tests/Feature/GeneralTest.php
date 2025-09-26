<?php

namespace Realodix\Haiku\Test\Feature;

use Realodix\Haiku\Test\TestCase;

class GeneralTest extends TestCase
{
    public function testComparesFiles(): void
    {
        $inputFile = base_path('tests/Integration/general_actual.txt');
        $expectedFile = base_path('tests/Integration/general_expected.txt');

        $this->assertFilter($expectedFile, $inputFile);
    }
}
