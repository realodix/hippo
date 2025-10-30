<?php

namespace Realodix\Hippo\Test\Unit\Filter;

use PHPUnit\Framework\Attributes as PHPUnit;
use Realodix\Hippo\Fixer\Processor;
use Realodix\Hippo\Test\TestCase;

class CosmeticTest extends TestCase
{
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = app(Processor::class);
    }

    // ========================================================================
    // General & File Structure Tests
    // ========================================================================

    #[PHPUnit\Test]
    public function blank_lines_are_removed(): void
    {
        $input = ['line1', '', 'line2', '   ', 'line3'];
        $expected = ['line1', 'line2', 'line3'];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function multiple_sections_are_processed_correctly(): void
    {
        $input = [
            '! Section 1: Network',
            '||example.com/ad',
            '||example.org/ad',
            '! Section 2: Element',
            'example.com##.ad',
            'example.org##.ad',
        ];
        $expected = [
            '! Section 1: Network',
            '||example.com/ad',
            '||example.org/ad',
            '! Section 2: Element',
            'example.com,example.org##.ad',
        ];
        $this->assertSame($expected, $this->processor->process($input));
    }

    // ========================================================================
    // Element Hiding Tests (`elementtidy`)
    // ========================================================================

    #[PHPUnit\Test]
    public function domains_are_sorted(): void
    {
        $input = ['c.com,b.com,~a.com##.ad'];
        $expected = ['~a.com,b.com,c.com##.ad'];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function domain_exclusion_is_handled(): void
    {
        $input = ['~b.com,a.com##.ad'];
        $expected = ['a.com,~b.com##.ad'];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function element_rules_based_on_rules(): void
    {
        $input = [
            'a.com##.ad',
            'a.com,b.com##.ad',
            'a.com##.adRight',
            'a.com,b.com##.adRight',
        ];
        $expected = [
            'a.com,b.com##.ad',
            'a.com,b.com##.adRight',
        ];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function combines_rules_based_on_domain_type(): void
    {
        $input = [
            // maybeMixed & maybeMixed
            'a.com,b.com##.ad',
            'c.com##.ad',
            '~d.com,e.com##.ad',
            '!', // negated & negated
            '~a.com,~b.com##.ad',
            '~c.com##.ad',
            '!', // maybeMixed & negated
            'x.com##.ad',
            '~y.com##.ad',
        ];
        $expected = [
            'a.com,b.com,c.com,~d.com,e.com##.ad',
            '!',
            '~a.com,~b.com,~c.com##.ad',
            '!',
            'x.com##.ad',
            '~y.com##.ad',
        ];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function element_rules_with_different_selectors_are_not_combined(): void
    {
        $input = [
            'a.com##.ad1',
            'b.com##.ad2',
        ];
        $this->assertSame($input, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function element_combinator_whitespace_is_normalized(): void
    {
        $input = [
            '##.a  +  .b:has(  +c)',
            '##.a  .b:has(  c  )',
            '##.a  >  .b:has(  >c)',
            '##.a  ~  .b:has(  ~c)',
        ];
        $expected = [
            '##.a + .b:has(+ c)',
            '##.a .b:has(  c  )',
            '##.a > .b:has(> c)',
            '##.a ~ .b:has(~ c)',
        ];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function element_pseudo_classes_are_lowercased(): void
    {
        $input = [
            '##div:hAs(>.ad)',
            '##div:nOt(>.ad)',
            '!',
            '##body.cookie-overlay-active::aFter',
        ];
        $expected = [
            '##div:has(> .ad)',
            '##div:not(> .ad)',
            '!',
            '##body.cookie-overlay-active::after',
        ];
        $this->assertSame($expected, $this->processor->process($input));
    }

    // ========================================================================
    // Scriptlet Tests (`elementtidy`)
    // ========================================================================

    #[PHPUnit\Test]
    public function scriptlet_domains_are_sorted(): void
    {
        $input = ['c.com,b.com,a.com##+js(...)'];
        $expected = ['a.com,b.com,c.com##+js(...)'];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function scriptlet_domain_exclusion_is_handled(): void
    {
        $input = ['~b.com,a.com##+js(...)'];
        $expected = ['a.com,~b.com##+js(...)'];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function scriptlet_rules_are_combined(): void
    {
        $input = [
            'a.com##+js(...)',
            'b.com##+js(...)',
            '!',
            'a.com##+js(...)',
            '~a.com,b.com##+js(...)',
        ];
        $expected = [
            'a.com,b.com##+js(...)',
            '!',
            'a.com,~a.com,b.com##+js(...)',
        ];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function scriptlet_rules_with_different_selectors_are_not_combined(): void
    {
        $input = [
            'a.com##+js(aopr, Notification)',
            'b.com##+js(aopw, Fingerprint2)',
        ];
        $this->assertSame($input, $this->processor->process($input));
    }
}
