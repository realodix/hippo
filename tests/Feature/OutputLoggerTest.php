<?php

namespace Realodix\Hippo\Test\Feature;

use Realodix\Hippo\OutputLogger;
use Realodix\Hippo\Test\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

class OutputLoggerTest extends TestCase
{
    public function testProcessedWritesSimpleMessageOnFullProcessing()
    {
        $testPath = 'path/to/another-file.txt';
        $absolutePath = base_path($testPath);

        $outputMock = \Mockery::mock(OutputInterface::class);
        $outputMock->expects('writeln')
            ->with("<info>[P]: {$testPath}</info>");

        $logger = new OutputLogger($outputMock);
        $logger->processed($absolutePath);
    }

    public function testSkippedWritesToOutputInVerboseMode()
    {
        $testPath = 'path/to/file.txt';
        $absolutePath = base_path($testPath);

        $outputMock = \Mockery::mock(OutputInterface::class);
        // Set isVerbose() to return true
        $outputMock->expects('isVerbose')
            ->andReturn(true);
        // Set expectations: writeln() should be called 1x with a valid string
        $outputMock->expects('writeln')
            ->with("<fg=gray>[S]: {$testPath}</>");

        $logger = new OutputLogger($outputMock);
        $logger->skipped($absolutePath);
    }

    public function testSkippedDoesNotWriteToOutputInNormalMode()
    {
        $outputMock = \Mockery::mock(OutputInterface::class);
        // Set isVerbose() to return false
        $outputMock->expects('isVerbose')
            ->andReturn(false);
        // Set expectations: writeln() should not be called at all
        $outputMock->expects('writeln')
            ->never();

        $logger = new OutputLogger($outputMock);
        $logger->skipped('path/to/file.txt');
    }

    public function testProcessedWritesDetailedMessageOnPartialProcessing()
    {
        $testPath = 'path/to/partial-file.txt';
        $absolutePath = base_path($testPath);

        $outputMock = \Mockery::mock(OutputInterface::class);
        $outputMock->expects('writeln')
            ->with("<info>[P]: {$testPath} (processed 5/10 blocks)</info>");

        $logger = new OutputLogger($outputMock);
        $logger->processed($absolutePath, 5, 10);
    }

    public function testErrorWritesToOutput()
    {
        $errorMessage = 'Something went wrong.';

        $outputMock = \Mockery::mock(OutputInterface::class);
        $outputMock->expects('writeln')
            ->with("<fg=red>[E]: {$errorMessage}</>");

        $logger = new OutputLogger($outputMock);
        $logger->error($errorMessage);
    }
}
