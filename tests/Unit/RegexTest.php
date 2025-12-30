<?php

namespace Realodix\Haiku\Test\Unit;

use PHPUnit\Framework\Attributes as PHPUnit;
use Realodix\Haiku\Fixer\Regex;
use Realodix\Haiku\Test\TestCase;

class RegexTest extends TestCase
{
    use GeneralProvider;

    #[PHPUnit\DataProvider('not_network_option_provider')]
    #[PHPUnit\Test]
    public function not_network_option($string)
    {
        $this->assertFalse((bool) preg_match(Regex::NET_OPTION, $string));
    }

    public static function not_network_option_provider()
    {
        return [
            ['#$#.textad { visibility: hidden; }'],
            ['example.com#@$#.textad { visibility: hidden; }'],
            ['example.com#$#div.Ad-Container[id^="adblock-bait-element-"] { display: block !important; }'],
            ['example.com#$?#.banner { display: none; debug: true; }'],
            ['example.com#@$?#.banner { display: none; debug: true; }'],
        ];
    }

    #[PHPUnit\DataProvider('network_option_domain_provider')]
    #[PHPUnit\Test]
    public function network_option_domain($expected, $string)
    {
        preg_match(Regex::NET_OPTION_DOMAIN, $string, $matches);
        $this->assertSame($expected, $matches[1] ?? null);
    }

    public static function network_option_domain_provider()
    {
        return [
            ['example.com', '$domain=example.com'],
            ['example.com|example.org', ',domain=example.com|example.org'],
            ['example.net', '$denyallow=example.net'],
            ['example.org', ',from=example.org'],
            ['example.co.uk', '$to=example.co.uk'],
            ['post', ',method=post'],
            ['~example.com', '$domain=~example.com'],
            ['example.com|~sub.example.com', '$domain=example.com|~sub.example.com'],
            ['example.org', '$script,domain=example.org'],
            [null, '$domain=example.com,script'],
            [null, '$domain='],
            [null, 'domain=example.com'],
            [null, '$domains=example.com'],
            [null, '||example.com^'],
        ];
    }

    #[PHPUnit\DataProvider('isCosmeticRuleProvider')]
    #[PHPUnit\Test]
    public function cosmetic_rule($string)
    {
        $this->assertTrue((bool) preg_match(Regex::COSMETIC_RULE, $string));
    }

    #[PHPUnit\DataProvider('cosmeticRuleMatchProvider')]
    #[PHPUnit\Test]
    public function cosmetic_rule_match(
        $rule,
        $expectedMatch,
        $expectedDomain,
        $expectedSeparator,
        $expectedRule,
    ) {
        $matched = preg_match(Regex::COSMETIC_RULE, $rule, $m);

        // There should be no matches at all
        if ($expectedMatch === null) {
            $this->assertSame(0, $matched);

            return;
        }

        $this->assertSame($expectedMatch, $m[0], "Full match: $rule");
        $this->assertSame($expectedDomain, $m[2], "Extracted domain: $rule");
        $this->assertSame($expectedSeparator, $m[3], "Extracted separator: $rule");
        $this->assertSame($expectedRule, $m[4], "Extracted rule: $rule");
    }

    public static function cosmeticRuleMatchProvider(): array
    {
        return [
            ['##div', '##div', '', '##', 'div'],

            [
                'example.com,~example.org##div',
                'example.com,~example.org##div',
                'example.com,~example.org',
                '##',
                'div',
            ],

            [
                'example.com,~example.org##.ads',
                'example.com,~example.org##.ads',
                'example.com,~example.org',
                '##',
                '.ads',
            ],

            [
                '[$app=org.example.app]example.com##.textad',
                '[$app=org.example.app]example.com##.textad',
                'example.com',
                '##',
                '.textad',
            ],

            ['example.com##+js(...)', 'example.com##+js(...)', 'example.com', '##+', 'js(...)'],
            ['/regex/##div', '/regex/##div', '/regex/', '##', 'div'],

            [
                '[$app=~org.example.app1|~org.example.app2]example.com##.textad',
                '[$app=~org.example.app1|~org.example.app2]example.com##.textad',
                'example.com',
                '##',
                '.textad',
            ],

            [
                'example.com#%#//scriptlet(...)',
                'example.com#%#//scriptlet(...)',
                'example.com', '#%#//',
                'scriptlet(...)',
            ],

            // not included
            ['example.com#%#window.__gaq = undefined;', null, null, null, null],
        ];
    }

    #[PHPUnit\DataProvider('cosmeticDomainProvider')]
    #[PHPUnit\Test]
    public function cosmetic_domain($rule, $expectedMatch, $expectedDomain)
    {
        $matched = preg_match(Regex::COSMETIC_DOMAIN, $rule, $m);

        // There should be no matches at all
        if ($expectedMatch === null) {
            $this->assertSame(0, $matched);

            return;
        }

        $this->assertSame($expectedMatch, $m[0], "Full match: $rule");
        $this->assertSame($expectedDomain, $m[1], "Extracted domain: $rule");
    }

    public static function cosmeticDomainProvider(): array
    {
        return [
            ['##div', '##', ''],
            ['example.com,~example.org##div', 'example.com,~example.org##', 'example.com,~example.org'],
            ['example.com,~example.org##.ads', 'example.com,~example.org##', 'example.com,~example.org'],

            ['example.com##+js(...)', 'example.com##', 'example.com'],
            ['example.com#%#//scriptlet(...)', 'example.com#%#', 'example.com'],
            ['example.com#%##%#window.__gaq = undefined;', 'example.com#%#', 'example.com'],

            // not included
            ['/regex/##div', null, null],
            ['[$app=~org.example.app1|~org.example.app2]example.com##.textad', null, null],
        ];
    }
}
