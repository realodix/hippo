<?php

namespace Realodix\Haiku\Test\Feature;

use PHPUnit\Framework\Attributes as PHPUnit;
use Realodix\Haiku\Console\BuildCommand;
use Realodix\Haiku\Test\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class BuilderTest extends TestCase
{
    protected function runBuildCommand()
    {
        $application = new Application;
        $application->addCommand(app(BuildCommand::class));
        $command = $application->find('build');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--config' => 'tests/Integration/Builder/haiku.yml',
            '--force' => true,
        ]);
    }

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
