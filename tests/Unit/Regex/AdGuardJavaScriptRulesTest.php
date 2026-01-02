<?php

namespace Realodix\Haiku\Test\Unit\Regex;

use PHPUnit\Framework\Attributes as PHPUnit;
use Realodix\Haiku\Fixer\Regex;
use Realodix\Haiku\Test\TestCase;

class AdGuardJavaScriptRulesTest extends TestCase
{
    #[PHPUnit\Test]
    public function isAdGuardJavaScriptRules()
    {
        $str = '#%#window.__gaq = undefined;';
        $this->assertTrue((bool) preg_match(Regex::AG_JS_RULE, $str));

        $str = 'example.com#@%#window.__gaq = undefined;';
        $this->assertTrue((bool) preg_match(Regex::AG_JS_RULE, $str));

        $str = '#@%#window.__gaq = undefined;';
        $this->assertTrue((bool) preg_match(Regex::AG_JS_RULE, $str));
    }

    #[PHPUnit\DataProvider('javaScriptRulesMatchProvider')]
    #[PHPUnit\Test]
    public function javaScriptRules_match(
        $rule, $expectedMatch, $expectedDomain, $expectedSeparator, $expectedRule,
    ) {
        preg_match(Regex::AG_JS_RULE, $rule, $m);

        $this->assertSame($expectedMatch, $m[0], "Full match: $rule");
        $this->assertSame($expectedDomain, $m[1], "Extracted domain: $rule");
        $this->assertSame($expectedSeparator, $m[2], "Extracted separator: $rule");
        $this->assertSame($expectedRule, $m[3], "Extracted rule: $rule");
    }

    public static function javaScriptRulesMatchProvider(): array
    {
        return [
            [
                '#%#window.__gaq = undefined;',
                '#%#window.__gaq = undefined;',
                '',
                '#%#',
                'window.__gaq = undefined;',
            ],
            [
                '#@%#window.__gaq = undefined;',
                '#@%#window.__gaq = undefined;',
                '',
                '#@%#',
                'window.__gaq = undefined;',
            ],

            [
                'example.com,~auth.example.com#@%#window.__gaq = undefined;',
                'example.com,~auth.example.com#@%#window.__gaq = undefined;',
                'example.com,~auth.example.com',
                '#@%#',
                'window.__gaq = undefined;',
            ],
        ];
    }
}
