<?php

namespace Realodix\Haiku\Test\Feature;

use PHPUnit\Framework\Attributes as PHPUnit;
use Realodix\Haiku\Test\TestCase;

class BuilderTest extends TestCase
{
    public function testBuild()
    {
        $this->runBuildCommand();

        $this->assertFileEquals(
            base_path('tests/Integration/Builder/result/compiled1.txt'),
            base_path('tests/Integration/tmp/compiled1.txt'),
        );
    }

    public function testBuild2()
    {
        $this->runBuildCommand();

        $this->assertFileEquals(
            base_path('tests/Integration/Builder/result/compiled2.txt'),
            base_path('tests/Integration/tmp/compiled2.txt'),
        );
    }

    #[PHPUnit\Test]
    public function date_modified_without_metadata()
    {
        $this->runBuildCommand();

        $this->assertStringNotContainsString(
            'Last modified:',
            file_get_contents(base_path('tests/Integration/tmp/date_modified.txt')),
        );
    }

    #[PHPUnit\Test]
    public function date_modified_metadata_provided()
    {
        $this->runBuildCommand();

        $this->assertStringContainsString(
            'Last modified:',
            file_get_contents(base_path('tests/Integration/tmp/date_modified_metadata_provided.txt')),
        );
    }
}
