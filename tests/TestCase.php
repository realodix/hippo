<?php

namespace Realodix\Haiku\Test;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Realodix\Haiku\Console\FixCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

abstract class TestCase extends BaseTestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public $tmpDir = __DIR__.'/Integration/tmp';

    public $cacheFile = __DIR__.'/Integration/tmp/cache.json';

    protected Filesystem $fs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fs = new Filesystem;

        // In the test environment, we bind the OutputInterface to a silent,
        // buffered output so that command output isn't printed during tests.
        app()->singleton(
            \Symfony\Component\Console\Output\OutputInterface::class,
            \Symfony\Component\Console\Output\BufferedOutput::class,
        );
    }

    protected function runFixCommand($processingFile, ?string $cachePath = null, array $options = [])
    {
        $application = new Application;
        $application->addCommand(app(FixCommand::class));
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
        $processingFile = Path::join($this->tmpDir, basename($actualFile));
        $this->fs->copy($actualFile, $processingFile, true);

        $this->runFixCommand($processingFile, $cachePath, $options);

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
        if ($this->fs->exists($this->tmpDir)) {
            $files = array_merge(
                glob(Path::join($this->tmpDir, '/*.txt')),
                glob(Path::join($this->tmpDir, '/*.json')),
                glob(Path::join($this->tmpDir, '/.*.json')),
            );

            if ($files) {
                $this->fs->remove($files);
            }
        }
    }
}
