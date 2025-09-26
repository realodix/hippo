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
            ['$Denyallow=/[A-Z-a-z-09]+/', '$denyallow=/[A-Z-a-z-09]+/'],
            ['$Domain=/[A-Z-a-z-09]+/', '$domain=/[A-Z-a-z-09]+/'],
            ['$From=/[A-Z-a-z-09]+/', '$from=/[A-Z-a-z-09]+/'],
            ['$Method=/[A-Z-a-z-09]+/', '$method=/[A-Z-a-z-09]+/'],
            ['$To=/[A-Z-a-z-09]+/', '$to=/[A-Z-a-z-09]+/'],

            ['||example.org^$Csp=Foo', '||example.org^$csp=Foo'],
            ['||example.org^$Permissions=Foo', '||example.org^$permissions=Foo'],
            ['||example.org^$Reason=Foo', '||example.org^$reason=Foo'],
            ['||example.org^$Removeparam=Foo', '||example.org^$removeparam=Foo'],
            ['||example.org^$Replace=Foo', '||example.org^$replace=Foo'],
            ['||example.org^$Urlskip=Foo', '||example.org^$urlskip=Foo'],
            ['||example.org^$Urltransform=Foo', '||example.org^$urltransform=Foo'],

            ['||example.org^$Cookie=Foo', '||example.org^$cookie=Foo'],
            ['||example.org^$Hls=Foo', '||example.org^$hls=Foo'],
            ['||example.org^$Jsonprune=Foo', '||example.org^$jsonprune=Foo'],
            ['||example.org^$Xmlprune=Foo', '||example.org^$xmlprune=Foo'],
        ];
    }
}
