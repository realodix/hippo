<?php

namespace Realodix\Haiku\Test\Unit\Filter;

use PHPUnit\Framework\Attributes as PHPUnit;
use Realodix\Haiku\Fixer\Processor;
use Realodix\Haiku\Test\TestCase;

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
    public function rules_order(): void
    {
        $input = [
            '/ads.$domain=example.com',
            '||example.com^',
            '@@||example.com^',
        ];
        $expected = [
            '/ads.$domain=example.com',
            '||example.com^',
            '@@||example.com^',
        ];

        arsort($input);
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function combines_rules_based_on_rules(): void
    {
        $input = [
            '-banner-$image,domain=a.com',
            '-banner-$image,domain=a.com|b.com',
            '-banner-$image,domain=a.com,css',

            '||example.com^$domain=a.com',
            '||example.com^$domain=b.com',
            '||example.com^$domain=c.com,css',
        ];
        $expected = [
            '-banner-$css,image,domain=a.com',
            '-banner-$image,domain=a.com|b.com',
            '||example.com^$css,domain=c.com',
            '||example.com^$domain=a.com|b.com',
        ];
        $this->assertSame($expected, $this->processor->process($input));

        $input = [
            '$permissions=storage-access=()\, camera=(),domain=b.com|a.com,image',
            '$domain=b.com|a.com,permissions=storage-access=()\, camera=(),image',
            '$permissions=storage-access=()\, camera=(),domain=b.com|a.com,image',
        ];
        $expected = [
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
    public function option_sort__alphabetically_and_type(): void
    {
        $input = ['||example.com^$script,image,third-party,domain=a.com'];
        $expected = ['||example.com^$third-party,image,script,domain=a.com'];
        $this->assertSame($expected, $this->processor->process($input));

        $input = ['||example.com^$~image,image'];
        $expected = ['||example.com^$image,~image'];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function option_sort_order__highest(): void
    {
        $input = ['*$css,3p,third-party,strict3p,first-party,1p,strict1p,strict-first-party,strict-third-party'];
        $expected = ['*$strict-first-party,strict-third-party,strict1p,strict3p,1p,3p,first-party,third-party,css'];
        $this->assertSame($expected, $this->processor->process($input));

        // badfilter & important
        // badfilter must always be first
        $input = ['*$important,domain=3p.com,css,badfilter'];
        $expected = ['*$badfilter,important,css,domain=3p.com'];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\DataProvider('option_sort_order__has_domain_provider')]
    #[PHPUnit\Test]
    public function option_sort_order__has_domain(array $input, array $expected): void
    {
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\DataProvider('option_sort_order__has_value_provider')]
    #[PHPUnit\Test]
    public function option_sort_order__has_value(array $input, array $expected): void
    {
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
    public function optDomain_values_are_sorted(): void
    {
        $input = ['$domain=c.com|a.com|~b.com'];
        $expected = ['$domain=a.com|~b.com|c.com'];
        $this->assertSame($expected, $this->processor->process($input));

        $input = ['$from=c.com|a.com|~b.com,to=c.com|a.com|~b.com'];
        $expected = ['$from=a.com|~b.com|c.com,to=a.com|~b.com|c.com'];
        $this->assertSame($expected, $this->processor->process($input));

        $input = ['$denyallow=c.com|a.com|~b.com'];
        $expected = ['$denyallow=a.com|~b.com|c.com'];
        $this->assertSame($expected, $this->processor->process($input));

        $input = ['$method=post|~get|delete'];
        $expected = ['$method=delete|~get|post'];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function optDomain_values_are_lowercase(): void
    {
        $input = [
            '$DENYALLOW=ExamPle.Com',
            '$DOMAIN=ExamPle.Com',
            '$FROM=ExamPle.Com',
            '$TO=ExamPle.Com',
            '!',
            '$METHOD=GET',
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
    public function option_transforms(): void
    {
        // `$_`
        $input = [
            '||example.com$_,removeparam=/^ss\\$/,_,image',
            '||example.com$replace=/bad/good/,___,~third-party',
        ];
        $expected = [
            '||example.com$image,removeparam=/^ss\$/',
            '||example.com$~third-party,replace=/bad/good/',
        ];
        $this->assertSame($expected, $this->processor->process($input));

        // $empty
        $input = ['||example.com/js/net.js$script,empty,domain=example.org'];
        $expected = ['||example.com/js/net.js$script,redirect=nooptext,domain=example.org'];
        $this->assertSame($expected, $this->processor->process($input));

        // $mp4
        $input = ['||example.com/video/*.mp4$mp4,domain=example.org'];
        $expected = ['||example.com/video/*.mp4$media,redirect=noopmp4-1s,domain=example.org'];
        $this->assertSame($expected, $this->processor->process($input));
    }

    #[PHPUnit\Test]
    public function handle_regex_domains(): void
    {
        $input = [
            '/ads.$domain=/example\.com/', // current is regex
            '/ads.$domain=example.com',
            '@@/ads.$domain=example.com', // next is regex
            '@@/ads.$domain=/example\.com/',
            '/ads.$domain=/example\.com/',
            '/ads.$domain=/example\.com/',
        ];
        $expected = [
            '/ads.$domain=/example\.com/',
            '/ads.$domain=example.com',
            '@@/ads.$domain=/example\.com/',
            '@@/ads.$domain=example.com',
        ];
        $this->assertSame($expected, $this->processor->process($input));
    }
}
