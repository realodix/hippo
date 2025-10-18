<?php

namespace Realodix\Hippo\Test\Feature;

use Realodix\Hippo\OutputLogger;
use Realodix\Hippo\Test\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

class OutputLoggerTest extends TestCase
{
    public function testProcessedWritesSimpleMessageOnFullProcessing()
    {
        $testPath = 'path/to/another-file.txt';
        $absolutePath = getcwd().DIRECTORY_SEPARATOR.$testPath;

        $outputMock = $this->createMock(OutputInterface::class);
        $outputMock->expects($this->once())
            ->method('writeln')
            ->with("<info>[P]: {$testPath}</info>");

        $logger = new OutputLogger($outputMock);
        $logger->processed($absolutePath);
    }

    public function testSkippedWritesToOutputInVerboseMode()
    {
        $testPath = 'path/to/file.txt';
        // getcwd() in OutputLogger will be the project root.
        // Path::makeRelative will result in just 'path/to/file.txt'
        $absolutePath = getcwd().DIRECTORY_SEPARATOR.$testPath;

        $outputMock = $this->createMock(OutputInterface::class);

        // 1. Atur agar isVerbose() mengembalikan true
        $outputMock->method('isVerbose')->willReturn(true);

        // 2. Atur ekspektasi: writeln() harus dipanggil 1x dengan string yang benar
        $outputMock->expects($this->once())
            ->method('writeln')
            ->with("<fg=gray>[S]: {$testPath}</>");

        // 3. Buat logger dengan mock output
        $logger = new OutputLogger($outputMock);

        // 4. Jalankan metodenya
        $logger->skipped($absolutePath);
    }

    public function testSkippedDoesNotWriteToOutputInNormalMode()
    {
        $outputMock = $this->createMock(OutputInterface::class);

        // 1. Atur agar isVerbose() mengembalikan false
        $outputMock->method('isVerbose')->willReturn(false);

        // 2. Atur ekspektasi: writeln() tidak boleh dipanggil sama sekali
        $outputMock->expects($this->never())
            ->method('writeln');

        $logger = new OutputLogger($outputMock);
        $logger->skipped('path/to/file.txt');
    }

    public function testProcessedWritesDetailedMessageOnPartialProcessing()
    {
        $testPath = 'path/to/partial-file.txt';
        $absolutePath = getcwd().DIRECTORY_SEPARATOR.$testPath;

        $outputMock = $this->createMock(OutputInterface::class);
        $outputMock->expects($this->once())
            ->method('writeln')
            ->with("<info>[P]: {$testPath} (processed 5/10 blocks)</info>");

        $logger = new OutputLogger($outputMock);
        $logger->processed($absolutePath, 5, 10);
    }

    public function testErrorWritesToOutput()
    {
        $errorMessage = 'Something went wrong.';

        $outputMock = $this->createMock(OutputInterface::class);
        $outputMock->expects($this->once())
            ->method('writeln')
            ->with("<fg=red>[E]: {$errorMessage}</>");

        $logger = new OutputLogger($outputMock);
        $logger->error($errorMessage);
    }
}
