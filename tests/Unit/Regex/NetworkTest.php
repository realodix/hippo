<?php

namespace Realodix\Haiku\Test\Unit\Regex;

use PHPUnit\Framework\Attributes as PHPUnit;
use Realodix\Haiku\Fixer\Regex;
use Realodix\Haiku\Test\TestCase;

class NetworkTest extends TestCase
{
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

    #[PHPUnit\DataProvider('network_option_split_provider')]
    #[PHPUnit\Test]
    public function network_option_split($string, $expected)
    {
        $v = preg_split(Regex::NET_OPTION_SPLIT, $string);

        $this->assertSame($expected, $v);
    }

    public static function network_option_split_provider()
    {
        return [
            [
                '$~third-party,~xmlhttprequest,domain=~www.example.com',
                ['$~third-party', '~xmlhttprequest', 'domain=~www.example.com'],
            ],
            [
                '$_,removeparam=/^ss\\$/,__,image,1p,3p',
                ['$_', 'removeparam=/^ss\$/,__', 'image', '1p', '3p'],
            ],

            // only network options, then the filter rules will also be captured
            [
                '||example.com/*.js$1p,script',
                ['||example.com/*.js$1p', 'script'],
            ],

            // typo
            [ // uppercase network option
                '$IMAGE,DOMAIN=a.com|b.com',
                ['$IMAGE', 'DOMAIN=a.com|b.com'],
            ],
            [ // has superfluous commas
                '*$,script,,header=via:/1\.1\s+google/,,css,',
                ['*$', 'script', '', 'header=via:/1\.1\s+google/', '', 'css', ''],
            ],

            // ignore: escape comma
            [
                '$image,permissions=storage-access=()\, camera=(),domain=a.com|b.com',
                ['$image', 'permissions=storage-access=()\, camera=()', 'domain=a.com|b.com'],
            ],
            [
                '||example.org^$hls=/#UPLYNK-SEGMENT:.*\,ad/t,domain=/a\,b/',
                ['||example.org^$hls=/#UPLYNK-SEGMENT:.*\,ad/t', 'domain=/a\,b/'],
            ],

            // ignore: comma inside regex
            [
                '/ads.$domain=/^https:\/\/[a-z\d]{4,}+\.[a-z\d]{12,}+\.(cfd|sbs|shop)$/',
                ['/ads.$domain=/^https:\/\/[a-z\d]{4,}+\.[a-z\d]{12,}+\.(cfd|sbs|shop)$/'],
            ],
            [
                '/^https:\/\/[a-z\d]{4,}+\.[a-z\d]{12,}+\.(cfd|sbs|shop)$/',
                ['/^https:\/\/[a-z\d]{4,}+\.[a-z\d]{12,}+\.(cfd|sbs|shop)$/'],
            ],
        ];
    }
}
