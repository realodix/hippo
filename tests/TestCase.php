<?php

namespace Realodix\Hippo\Test;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Realodix\Hippo\Console\FixCommand;
use Realodix\Hippo\Helper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

abstract class TestCase extends BaseTestCase
{
    public $tmpDir = __DIR__.'/Integration/tmp';

    public $cacheFile = __DIR__.'/Integration/tmp/cache.json';

    public $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = Helper::app();

        // In the test environment, we bind the OutputInterface to a silent,
        // buffered output so that command output isn't printed during tests.
        Helper::app()->singleton(
            \Symfony\Component\Console\Output\OutputInterface::class,
            \Symfony\Component\Console\Output\BufferedOutput::class,
        );
    }

    protected function runCommand($processingFile, ?string $cachePath = null, array $options = [])
    {
        $application = new Application;
        $application->add(Helper::app(FixCommand::class));
        $command = $application->find('fix');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array_merge([
            '--path' => $processingFile,
            '--cache' => $cachePath,
        ], $options));
    }

    protected function assertFilter(string $expectedFile, string $actualFile, ?string $cachePath = null, array $options = [])
    {
        $cachePath = $cachePath ?? $this->cacheFile;
        $processingFile = $this->tmpDir.'/'.basename($actualFile);
        $filesystem = new Filesystem;
        $filesystem->copy($actualFile, $processingFile, true);

        $this->runCommand($processingFile, $cachePath, $options);

        $this->assertFileEquals($expectedFile, $processingFile);
    }

    // Helper to call private/protected methods for testing
    protected function callPrivateMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);

        return $method->invokeArgs($object, $parameters);
    }

    // Helper to get private/protected properties for testing
    protected function getPrivateProperty(object $object, string $propertyName): mixed
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);

        return $property->getValue($object);
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem;
        if ($filesystem->exists($this->tmpDir)) {
            $files = glob($this->tmpDir.'/*.txt');
            if ($files) {
                $filesystem->remove($files);
            }

            $files = glob($this->tmpDir.'/*.json');
            if ($files) {
                $filesystem->remove($files);
            }
        }
    }
}
