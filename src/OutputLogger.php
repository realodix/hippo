<?php

namespace Realodix\Hippo;

use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

final class OutputLogger
{
    private string $root;

    public function __construct(
        private OutputInterface $output,
    ) {
        $this->root = base_path();
    }

    public function processing(string $path): void
    {
        $path = Path::makeRelative($path, $this->root);

        $this->output->writeln("<fg=gray>[P]: $path</>");
    }

    /**
     * Logs a processed file.
     *
     * @param string $path The path to the processed file.
     * @param bool $overwrite Whether to clear the previous line.
     */
    public function processed(string $path, bool $overwrite = false): void
    {
        $path = Path::makeRelative($path, $this->root);

        if ($overwrite) {
            // $this->output->write("\x1b[1A\x1b[2K");
            $cursor = new Cursor($this->output);
            $cursor->moveUp()->clearLine();
        }

        $this->output->writeln("<info>[P]: $path</info>");
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
