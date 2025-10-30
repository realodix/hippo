<?php

namespace Realodix\Hippo\Test\Unit;

trait GeneralProvider
{
    public static function isSpecialLineProvider(): array
    {
        return [
            // Comment line
            ['!'],
            ['! comment'],
            // Special comment
            ['#comment'],
            ['# comment'],
            ['##'],
            ['###'],

            // Headers
            ['[Adblock Plus 2.0]'],

            // Preprocessor directive
            // https://github.com/gorhill/uBlock/wiki/Static-filter-syntax#pre-parsing-directives
            // https://adguard.com/kb/general/ad-filtering/create-own-filters/#preprocessor-directives
            ['!#include ublock-filters.txt'],
            ['!#if env_firefox'],
            ['!#if (conditions)'],
            ['!#else'],
            ['!#endif'],
            ['!+ NOT_OPTIMIZED'],
            ['!+ NOT_OPTIMIZED PLATFORM(android)'],

            // python-abp directive
            // https://github.com/adblockplus/python-abp
            ['%include easylist:easylist/easylist_general_block.txt%'],
        ];
    }

    public static function isCosmeticRuleProvider(): array
    {
        return [
            // Element hiding rules
            // https://adguard.com/kb/general/ad-filtering/create-own-filters/#cosmetic-elemhide-rules
            ['##.ads'],
            ['###img'],
            ['##img'],
            ['#@#.ads'],
            ['#@##img'],
            ['#@#img'],

            // CSS rules
            // https://adguard.com/kb/general/ad-filtering/create-own-filters/#cosmetic-css-rules
            ['#$#div { visibility: hidden; }'],
            ['#@$#div { visibility: hidden; }'],
            ['#$#.textad { visibility: hidden; }'],
            ['#@$#.textad { visibility: hidden; }'],
            ['#$##textad { visibility: hidden; }'],
            ['#@$##textad { visibility: hidden; }'],

            // Extended CSS selectors
            // https://adguard.com/kb/general/ad-filtering/create-own-filters/#extended-css-selectors
            ['#?#div:has(> a[target="_blank"][rel="nofollow"])'],
            ['#@?#div:has(> a[target="_blank"][rel="nofollow"])'],
            ['#?#.banner:matches-css(width: 360px)'],
            ['#@?#.banner:matches-css(width: 360px)'],
            ['#?##banner:matches-css(width: 360px)'],
            ['#@?##banner:matches-css(width: 360px)'],

            ['#$?#div:has(> span) { display: none !important; }'],
            ['#@$?#div:has(> span) { display: none !important; }'],
            ['#$?#.banner:has(> span) { display: none !important; }'],
            ['#@$?#.banner:has(> span) { display: none !important; }'],
            ['#$?##banner:has(> span) { display: none !important; }'],
            ['#@$?##banner:has(> span) { display: none !important; }'],

            // JavaScript rules
            // https://adguard.com/kb/general/ad-filtering/create-own-filters/#javascript-rules
            // https://adguard.com/kb/general/ad-filtering/create-own-filters/#scriptlets
            // ['#%#window.__gaq = undefined;'],
            // ['#@%#window.__gaq = undefined;'],
        ];
    }

    public static function isNotCosmeticRuleProvider(): array
    {
        return [
            // Element hiding rules
            ['## .ads'],
            ['## #img'],
            ['## img'],
            ['#@# .ads'],
            ['#@# #img'],
            ['#@# img'],
            // CSS rules
            ['#$# div { visibility: hidden; }'],
            ['#@$# div { visibility: hidden; }'],
            ['#$# .textad { visibility: hidden; }'],
            ['#@$# .textad { visibility: hidden; }'],
            ['#$# #textad { visibility: hidden; }'],
            ['#@$# #textad { visibility: hidden; }'],
            // Extended CSS selectors
            ['#?# div:has(> a[target="_blank"][rel="nofollow"])'],
            ['#@?# div:has(> a[target="_blank"][rel="nofollow"])'],
            ['#?# .banner:matches-css(width: 360px)'],
            ['#@?# .banner:matches-css(width: 360px)'],
            ['#?# #banner:matches-css(width: 360px)'],
            ['#@?# #banner:matches-css(width: 360px)'],
            ['#$?# div:has(> span) { display: none !important; }'],
            ['#@$?# div:has(> span) { display: none !important; }'],
            ['#$?# .banner:has(> span) { display: none !important; }'],
            ['#@$?# .banner:has(> span) { display: none !important; }'],
            ['#$?# #banner:has(> span) { display: none !important; }'],
            ['#@$? ##banner:has(> span) { display: none !important; }'],
            // JavaScript rules
            ['#%# window.__gaq = undefined;'],
            ['#@%# window.__gaq = undefined;'],

            // Comment
            ['############################################'],
            ['#202509090000'],
            ['#foo'],
            ['# foo'],
        ];
    }
}
