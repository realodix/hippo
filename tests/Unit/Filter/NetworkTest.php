<?php

namespace Realodix\Hippo\Test\Unit\Filter;

use PHPUnit\Framework\Attributes as PHPUnit;
use Realodix\Hippo\Fixer\Processor;
use Realodix\Hippo\Test\TestCase;

class NetworkTest extends TestCase
{
    use NetworkProvider;

    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = app(Processor::class);
    }

    #[PHPUnit\Test]
    public function element_rules_based_on_rules(): void
    {
        $input = [
            '-banner-$image,domain=a.com',
            '-banner-$image,domain=a.com|b.com',
            '||example.com^$domain=a.com',
            '||example.com^$domain=b.com',
            '!',
            '$domain=b.com|a.com,permissions=storage-access=()\, camera=(),image',
            '$permissions=storage-access=()\, camera=(),domain=b.com|a.com,image',
            '$domain=b.com|a.com,permissions=storage-access=(),image',
            '$permissions=storage-access=()\, camera=(),domain=b.com|a.com,image',
        ];
        $expected = [
            '-banner-$image,domain=a.com|b.com',
            '||example.com^$domain=a.com|b.com',
            '!',
            '$image,permissions=storage-access=(),domain=a.com|b.com',
            '$image,permissions=storage-access=()\, camera=(),domain=a.com|b.com',
        ];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function combines_rules_based_on_domain_type(): void
    {
        $input = [
            // maybeMixed & maybeMixed
            '||example.com^$domain=a.com|b.com',
            '||example.com^$domain=c.com',
            '||example.com^$domain=~d.com|e.com',
            '!', // negated & negated
            '||example.com^$domain=~a.com|~b.com',
            '||example.com^$domain=~c.com',
            '!', // maybeMixed & negated
            '||example.com^$domain=x.com',
            '||example.com^$domain=~y.com',
        ];
        $expected = [
            '||example.com^$domain=a.com|b.com|c.com|~d.com|e.com',
            '!',
            '||example.com^$domain=~a.com|~b.com|~c.com',
            '!',
            '||example.com^$domain=x.com',
            '||example.com^$domain=~y.com',
        ];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function sort_option_alphabetically_and_type(): void
    {
        $input = ['||example.com^$script,image,third-party,domain=a.com'];
        $expected = ['||example.com^$third-party,image,script,domain=a.com'];
        $this->assertSame($expected, $this->processor->process($input));

        $input = ['||example.com^$~image,image'];
        $expected = ['||example.com^$image,~image'];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function sort_option_priority_highest(): void
    {
        $input = ['*$domain=a.com,script,image,important,3p,third-party,strict3p,first-party,1p,strict1p'];
        $expected = ['*$important,strict1p,strict3p,1p,3p,first-party,third-party,image,script,domain=a.com'];
        $this->assertSame($expected, $this->processor->process($input));

        $input = ['*$domain=a.com,script,image,important,~3p,~third-party,strict3p,first-party,1p,strict1p'];
        $expected = ['*$important,strict1p,strict3p,1p,~3p,first-party,~third-party,image,script,domain=a.com'];
        $this->assertSame($expected, $this->processor->process($input));

        // badfilter & important
        // badfilter must always be first
        $input = ['*$important,domain=3p.com,css,badfilter'];
        $expected = ['*$badfilter,important,css,domain=3p.com'];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\DataProvider('sort_option_priority_has_domain_provider')]
    #[PHPUnit\Test]
    public function sort_option_priority_has_domain(array $input, array $expected): void
    {
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\DataProvider('sort_option_priority_has_value_provider')]
    #[PHPUnit\Test]
    public function sort_option_priority_has_value(array $input, array $expected): void
    {
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function sort_option_priority_badfilter(): void
    {
        $input = ['||example.com^$script,badfilter,image'];
        $expected = ['||example.com^$badfilter,image,script'];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function optDomain_values_are_sorted(): void
    {
        $input = ['||example.com^$domain=c.com|a.com|~b.com'];
        $expected = ['||example.com^$domain=a.com|~b.com|c.com'];
        $this->assertSame($expected, $this->processor->process($input));

        $input = ['||example.com^$from=b.com|a.com,to=d.com|c.com'];
        $expected = ['||example.com^$from=a.com|b.com,to=c.com|d.com'];
        $this->assertSame($expected, $this->processor->process($input));

        $input = ['||example.com^denyallow=y.com|x.com,domain=a.com|b.com'];
        $expected = ['||example.com^denyallow=y.com|x.com,domain=a.com|b.com'];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function optMethod_is_handled_and_sorted(): void
    {
        $input = ['||example.com^$method=post|get|delete,domain=~b.com|~a.com'];
        $expected = ['||example.com^$method=delete|get|post,domain=~a.com|~b.com'];
        $this->assertSame($expected, $this->processor->process($input));

        $input = ['||example.com^$domain=~b.com|~a.com,method=post|get|delete,'];
        $expected = ['||example.com^$method=delete|get|post,domain=~a.com|~b.com'];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function lowercase_the_option_name(): void
    {
        $input = ['||example.com^$ALL'];
        $expected = ['||example.com^$all'];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function lowercase_the_option_values(): void
    {
        $input = [
            '$DENYALLOW=ExamPle.Com',
            '$DOMAIN=ExamPle.Com',
            '$FROM=ExamPle.Com',
            '$METHOD=ExamPle.Com',
            '$TO=ExamPle.Com',
        ];

        $this->assertSame(array_map('strtolower', $input), $this->processor->process($input));
    }

    #[PHPUnit\DataProvider('lowercase_the_option_name_preserve_value_provider')]
    #[PHPUnit\Test]
    public function lowercase_the_option_name_preserve_value($input, $expected): void
    {
        $this->assertSame([$expected], $this->processor->process([$input]));
    }

    #[PHPUnit\Test]
    public function optCsp_option_is_preserved(): void
    {
        $input = ['||example.com^$csp=script-src \'none\''];
        $this->assertSame($input, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function optPermissions_option_is_handled(): void
    {
        $input = ['$permissions=storage-access=(),domain=b.com|a.com,image'];
        $expected = ['$image,permissions=storage-access=(),domain=a.com|b.com'];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function handle_escaped_comma(): void
    {
        $input = [
            '||example.org^$domain=/a\,b/,HLS=/#UPLYNK-SEGMENT:.*\,ad/t',
            '||example.org^$domain=b.com|a.com,permissions=storage-access=()\, camera=()',
            '$domain=b.com|a.com,PERMISSIONS=storage-access=()\, camera=()',
            '!',
            // Mengandung $, dan tidak boleh terpengaruh
            'example.com#$?#style[id="mdpDeblocker-css"] { remove: true; }',
            'example.com#%#(function(b){Object.defineProperty(Element.prototype,"innerHTML",{get:function(){return b.get.call(this)},set:function(a){/^(?:<([abisuq]) id="[^"]*"><\/\1>)*$/.test(a)||b.set.call(this,a)},enumerable:!0,configurable:!0})})(Object.getOwnPropertyDescriptor(Element.prototype,"innerHTML"));',
            'example.com#$#.ignielAdBlock { display: none !important; }',
            'example.com#$#div.Ad-Container[id^="adblock-bait-element-"] { display: block !important; }',
        ];
        $expected = [
            '$permissions=storage-access=()\, camera=(),domain=a.com|b.com',
            '||example.org^$hls=/#UPLYNK-SEGMENT:.*\,ad/t,domain=/a\,b/',
            '||example.org^$permissions=storage-access=()\, camera=(),domain=a.com|b.com',
            '!',
            // Mengandung $, dan tidak boleh terpengaruh
            'example.com#$#.ignielAdBlock { display: none !important; }',
            'example.com#$#div.Ad-Container[id^="adblock-bait-element-"] { display: block !important; }',
            'example.com#$?#style[id="mdpDeblocker-css"] { remove: true; }',
            'example.com#%#(function(b){Object.defineProperty(Element.prototype,"innerHTML",{get:function(){return b.get.call(this)},set:function(a){/^(?:<([abisuq]) id="[^"]*"><\/\1>)*$/.test(a)||b.set.call(this,a)},enumerable:!0,configurable:!0})})(Object.getOwnPropertyDescriptor(Element.prototype,"innerHTML"));',
        ];
        $this->assertSame($expected, $this->processor->process($input));
    }
}
