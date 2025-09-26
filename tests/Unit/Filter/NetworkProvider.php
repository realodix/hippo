<?php

namespace Realodix\Hippo\Test\Unit\Filter;

trait NetworkProvider
{
    public static function sort_option_priority_has_domain_provider(): array
    {
        return [
            // Domain
            [ // $denyallow
                ['||example.com^$3p,script,domain=a.com|b.com,denyallow=x.com|y.com'],
                ['||example.com^$3p,script,denyallow=x.com|y.com,domain=a.com|b.com'],
            ],
            [ // $domain
                ['||example.com^$script,domain=x.com,css'],
                ['||example.com^$css,script,domain=x.com'],
            ],
            [ // $to
                ['*$script,to=y.*|x.*,from=y.*|x.*,css'],
                ['*$css,script,from=x.*|y.*,to=x.*|y.*'],
            ],

            // $ipaddress
            // https://github.com/gorhill/uBlock/wiki/Static-filter-syntax#ipaddress
            [
                ['*$all,domain=~0.0.0.0|~127.0.0.1|~[::1]|~[::]|~local|~localhost,ipaddress=::,css'],
                ['*$all,css,domain=~0.0.0.0|~127.0.0.1|~[::1]|~[::]|~local|~localhost,ipaddress=::'],
            ],
        ];
    }

    public static function sort_option_priority_has_value_provider(): array
    {
        return [
            // $redirect / $redirect-rule
            [
                ['$script,redirect=noopjs,domain=x.com,css'],
                ['$css,script,redirect=noopjs,domain=x.com'],
            ],
            [
                ['$redirect-rule=noopjs,domain=x.com,css'],
                ['$css,redirect-rule=noopjs,domain=x.com'],
            ],

            // $removeparam
            [
                ['$~third-party,domain=x.com|y.com,removeparam,css'],
                ['$~third-party,css,removeparam,domain=x.com|y.com'],
            ],
            [
                ['$removeparam=/^(utm_source|utm_medium|utm_term)=/,domain=x.com,css'],
                ['$css,removeparam=/^(utm_source|utm_medium|utm_term)=/,domain=x.com'],
            ],

            // $permissions
            // https://github.com/gorhill/uBlock/wiki/Static-filter-syntax#permissions
            // https://adguard.com/kb/general/ad-filtering/create-own-filters/#permissions-modifier
            [
                ['|http*://*.*$doc,domain=~0.0.0.0|~127.0.0.1|~[::1]|~[::]|~local|~localhost,permissions=autoplay=(),css'],
                ['|http*://*.*$css,doc,permissions=autoplay=(),domain=~0.0.0.0|~127.0.0.1|~[::1]|~[::]|~local|~localhost'],
            ],
        ];
    }

    public static function lowercase_the_option_name_preserve_value_provider(): array
    {
        return [
            'singles' => [
                [
                    '||example.org^$cookie=NAME;maxAge=3600;sameSite=lax',
                    '||example.org^$hLs=/RegeXP/',
                    '||example.org^$remoVEpAram=~/RegeXP/',
                    '||example.org^$rEplAcE=/(<VAST[\s\S]*?>)[\s\S]*<\/VAST>/\$1<\/VAST>/i',
                    '||example.org^$urltransform=/RegeXP/i',
                ],
                [
                    '||example.org^$cookie=NAME;maxAge=3600;sameSite=lax',
                    '||example.org^$hls=/RegeXP/',
                    '||example.org^$removeparam=~/RegeXP/',
                    '||example.org^$replace=/(<VAST[\s\S]*?>)[\s\S]*<\/VAST>/\$1<\/VAST>/i',
                    '||example.org^$urltransform=/RegeXP/i',
                ],
            ],
            'with other options' => [
                [
                    '||example.org^$cookie=NAME;maxAge=3600;sameSite=lax,dOmAIn=A.cOm',
                    '||example.org^$hLs=/RegeXP/,dOmAIn=A.cOm',
                    '||example.org^$remoVEpAram=~/RegeXP/,dOmAIn=A.cOm',
                    '||example.org^$rEplAcE=/(<VAST[\s\S]*?>)[\s\S]*<\/VAST>/\$1<\/VAST>/i,dOmAIn=A.cOm',
                    '||example.org^$urltransform=/RegeXP/i,dOmAIn=A.cOm',
                ],
                [
                    '||example.org^$cookie=NAME;maxAge=3600;sameSite=lax,domain=a.com',
                    '||example.org^$hls=/RegeXP/,domain=a.com',
                    '||example.org^$removeparam=~/RegeXP/,domain=a.com',
                    '||example.org^$replace=/(<VAST[\s\S]*?>)[\s\S]*<\/VAST>/\$1<\/VAST>/i,domain=a.com',
                    '||example.org^$urltransform=/RegeXP/i,domain=a.com',
                ],
            ],
        ];
    }
}
