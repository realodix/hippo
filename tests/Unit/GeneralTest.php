<?php

namespace Realodix\Hippo\Test\Unit;

use PHPUnit\Framework\Attributes as PHPUnit;
use Realodix\Hippo\Helper;
use Realodix\Hippo\Processor\FilterProcessor;
use Realodix\Hippo\Test\TestCase;

class GeneralTest extends TestCase
{
    use GeneralProvider;

    /**
     * Remove the blank line
     */
    public function testBlankLine()
    {
        $processor = Helper::app(FilterProcessor::class);

        $input = [
            '',
            '     ',
        ];

        $expected = [];

        $output = $processor->process($input);

        $this->assertSame($expected, $output);
    }

    #[PHPUnit\Test]
    public function special_line()
    {
        $processor = Helper::app(FilterProcessor::class);

        $input = [
            '2',
            '1',
            '%include abid:src/general-block.adfl%',
            '%include abid:src/adservers.adfl%',
            '2',
            '1',
            '[AdGuard]',
            '[uBlock Origin]',
            '[Adblock Plus 2.0]',
            '2',
            '1',
            '',
            '    ',
            '2',
            '1',
        ];

        $expected = [
            '1',
            '2',
            '%include abid:src/general-block.adfl%',
            '%include abid:src/adservers.adfl%',
            '1',
            '2',
            '[AdGuard]',
            '[uBlock Origin]',
            '[Adblock Plus 2.0]',
            '1',
            '2',
        ];

        $output = $processor->process($input);

        $this->assertSame($expected, $output);
    }

    #[PHPUnit\DataProvider('isSpecialLineProvider')]
    #[PHPUnit\Test]
    public function isSpecialLine($data)
    {
        $processor = Helper::app(FilterProcessor::class);

        $this->assertTrue($processor->isSpecialLine($data));
    }

    #[PHPUnit\TestWith(['[$domain=example.com]##.textad'])]
    #[PHPUnit\TestWith(['[$domain=example.org]example.com##.textad'])]
    #[PHPUnit\TestWith(['%include'])]
    #[PHPUnit\Test]
    public function isNotSpecialLine($data)
    {
        $processor = Helper::app(FilterProcessor::class);

        $this->assertFalse($processor->isSpecialLine($data));
    }

    #[PHPUnit\DataProvider('isCosmeticRuleProvider')]
    #[PHPUnit\Test]
    public function isCosmeticRule($data)
    {
        $processor = Helper::app(FilterProcessor::class);

        $this->assertTrue(
            $processor->isCosmeticRule($data),
        );
    }

    #[PHPUnit\DataProvider('isNotCosmeticRuleProvider')]
    #[PHPUnit\Test]
    public function isNotCosmeticRule($data)
    {
        $processor = Helper::app(FilterProcessor::class);

        $this->assertFalse(
            $processor->isCosmeticRule($data),
        );
    }

    #[PHPUnit\Test]
    public function preserveTheStructure(): void
    {
        $inputFile = __DIR__.'/../Integration/preserve-the-structure_actual.txt';
        $expectedFile = __DIR__.'/../Integration/preserve-the-structure_expected.txt';

        $this->assertFilter($expectedFile, $inputFile);
    }
}
