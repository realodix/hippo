<?php

namespace Realodix\Haiku\Test\Unit\Filter;

use PHPUnit\Framework\Attributes as PHPUnit;
use Realodix\Haiku\Fixer\Processor;
use Realodix\Haiku\Test\TestCase;

class TypoTest extends TestCase
{
    use \Realodix\Haiku\Test\Unit\GeneralProvider;

    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = app(Processor::class);
    }

    #[PHPUnit\Test]
    public function domain_separator(): void
    {
        $input = [
            ',a.com,,b.com,##.ads',
            '||example.com^$domain=|a.com||b.com|',
        ];
        $expected = [
            '||example.com^$domain=a.com|b.com',
            'a.com,b.com##.ads',
        ];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function network_option_separator(): void
    {
        $input = [
            '||example.com^$domain=a.com|b.com,,css,',
        ];
        $expected = [
            '||example.com^$css,domain=a.com|b.com',
        ];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function domain_value(): void
    {
        $input = [
            '/ads.$domain=/Example.com/|.Example.com/|Example.com',
            '/ads2.$domain=.Example.com/',
            '/example.com/,.example.com/,example.com##.ads',
            '.example.com/##.ads2',
        ];
        $expected = [
            '/ads.$domain=example.com',
            '/ads2.$domain=example.com',
            'example.com##.ads',
            'example.com##.ads2',
        ];
        $this->assertSame($expected, $this->processor->process($input));

        // regex
        $input = [
            '/ads.$domain=/regex/',
            '/regex/##.ads',
        ];
        $expected = [
            '/ads.$domain=/regex/',
            '/regex/##.ads',
        ];
        $this->assertSame($expected, $this->processor->process($input));
    }
}
