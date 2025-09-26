<?php

namespace Realodix\Hippo\Test\Unit;

use PHPUnit\Framework\Attributes as PHPUnit;
use Realodix\Hippo\Fixer\Processor;
use Realodix\Hippo\Test\TestCase;

class GeneralTest extends TestCase
{
    use GeneralProvider;

    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = app(Processor::class);
    }

    /**
     * Remove the blank line
     */
    public function testBlankLine()
    {
        $input = [
            '',
            '     ',
        ];

        $expected = [];

        $output = $this->processor->process($input);

        $this->assertSame($expected, $output);
    }

    #[PHPUnit\Test]
    public function cleanup_the_spaces(): void
    {
        $input = [
            ' ! b',
            ' ! a',
            'a.com,,b.com##.ads',
            '||example.com^$domain=a.com||b.com,,css',
        ];
        $expected = [
            '! b',
            '! a',
            'a.com,b.com##.ads',
            '||example.com^$css,domain=a.com|b.com',
        ];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\DataProvider('notCombinedProvider')]
    #[PHPUnit\Test]
    public function not_combined($input, $expected): void
    {
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function special_line()
    {
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

        $output = $this->processor->process($input);

        $this->assertSame($expected, $output);
    }

    #[PHPUnit\DataProvider('isSpecialLineProvider')]
    #[PHPUnit\Test]
    public function isSpecialLine($data)
    {
        $this->assertTrue($this->processor->isSpecialLine($data));
    }

    #[PHPUnit\TestWith(['[$domain=example.com]##.textad'])]
    #[PHPUnit\TestWith(['[$domain=example.org]example.com##.textad'])]
    #[PHPUnit\TestWith(['%include'])]
    #[PHPUnit\Test]
    public function isNotSpecialLine($data)
    {
        $this->assertFalse($this->processor->isSpecialLine($data));
    }

    #[PHPUnit\DataProvider('isCosmeticRuleProvider')]
    #[PHPUnit\Test]
    public function isCosmeticRule($data)
    {
        $this->assertTrue($this->processor->isCosmeticRule($data));
    }

    #[PHPUnit\DataProvider('isNotCosmeticRuleProvider')]
    #[PHPUnit\Test]
    public function isNotCosmeticRule($data)
    {
        $this->assertFalse($this->processor->isCosmeticRule($data));
    }

    #[PHPUnit\Test]
    public function preserveTheStructure(): void
    {
        $inputFile = base_path('tests/Integration/preserve-the-structure_actual.txt');
        $expectedFile = base_path('tests/Integration/preserve-the-structure_expected.txt');

        $this->assertFilter($expectedFile, $inputFile);
    }

    /**
     * The results must not cause warnings/errors
     */
    #[PHPUnit\Test]
    public function bad_filter_causing_error(): void
    {
        // https://github.com/AdguardTeam/FiltersRegistry/blob/281518f967/filters/exclusions.txt#L16
        // https://github.com/realodix/hippo/blob/e7b8da5d78/src/Fixer/Type/ElementTidy.php#L35
        $input = [
            'example.com#@#',
            'example.com#?#',
            'example.com##',
            'example.com##+',
        ];
        $this->assertSame($input, $this->processor->process($input));
    }
}
