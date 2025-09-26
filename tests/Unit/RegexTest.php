<?php

namespace Realodix\Hippo\Test\Unit;

use PHPUnit\Framework\Attributes as PHPUnit;
use Realodix\Hippo\Fixer\Type\Regex;
use Realodix\Hippo\Test\TestCase;

class RegexTest extends TestCase
{
    use GeneralProvider;

    #[PHPUnit\DataProvider('not_net_option_provider')]
    #[PHPUnit\Test]
    public function not_net_option($string)
    {
        $this->assertFalse((bool) preg_match(Regex::NET_OPTION, $string));
    }

    public static function not_net_option_provider()
    {
        return [
            ['#$#.textad { visibility: hidden; }'],
            ['example.com#@$#.textad { visibility: hidden; }'],
            ['example.com#$#div.Ad-Container[id^="adblock-bait-element-"] { display: block !important; }'],
            ['example.com#$?#.banner { display: none; debug: true; }'],
            ['example.com#@$?#.banner { display: none; debug: true; }'],
        ];
    }

    #[PHPUnit\DataProvider('net_option_has_domain_provider')]
    #[PHPUnit\Test]
    public function net_option_has_domain($expected, $string)
    {
        preg_match(Regex::NET_OPTION_HAS_DOMAIN, $string, $matches);
        $this->assertSame($expected, $matches[1] ?? null);
    }

    public static function net_option_has_domain_provider()
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
}
