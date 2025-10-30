<?php

namespace Realodix\Hippo\Test\Feature;

use Realodix\Hippo\Console\BuildCommand;
use Realodix\Hippo\Test\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class BuilderTest extends TestCase
{
    protected function runBuildCommand()
    {
        $application = new Application;
        $application->add(app(BuildCommand::class));
        $command = $application->find('build');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--config' => 'tests/Integration/Builder/hippo.yml',
            '--force' => true,
        ]);
    }

    public function testBuild()
    {
        $this->runBuildCommand();

        $this->assertFileEquals(
            base_path('tests/Integration/Builder/compiled1.txt'),
            base_path('tests/Integration/tmp/compiled1.txt'),
        );
    }

    public function testBuild2()
    {
        $this->runBuildCommand();

        $this->assertFileEquals(
            base_path('tests/Integration/Builder/compiled2.txt'),
            base_path('tests/Integration/tmp/compiled2.txt'),
        );
    }
}
