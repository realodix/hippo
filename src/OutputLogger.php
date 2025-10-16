<?php

namespace Realodix\Hippo;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

final class OutputLogger
{
    private string $root;

    public function __construct(
        private OutputInterface $output,
    ) {
        $this->root = getcwd();
    }

    public function processed(string $path, ?int $processedBlockCount, ?int $totalBlocks): void
    {
        $path = Path::makeRelative($path, $this->root);

        if (
            (!is_null($processedBlockCount) || !is_null($totalBlocks))
            && $processedBlockCount !== $totalBlocks
        ) {
            $this->output->writeln(
                "<info>[P]: $path (processed $processedBlockCount/$totalBlocks blocks)</info>",
            );
        } else {
            $this->output->writeln("<info>[P]: $path</info>");
        }
    }

    public function skipped(string $path): void
    {
        $path = Path::makeRelative($path, $this->root);
        if ($this->output->isVerbose()) {
            $this->output->writeln("<fg=gray>[S]: $path</>");
        }
    }

    public function error(string $message): void
    {
        $this->output->writeln("<fg=red>[E]: $message</>");
    }
}
