<?php

namespace Realodix\Hippo\Console;

use Realodix\Hippo\Enums\Mode;
use Realodix\Hippo\Fixer\Fixer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'fix',
    description: 'Process adblock filter files in a directory or single file.',
)]
class FixCommand extends Command
{
    public function __construct(
        private Fixer $fixer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'File or directory to process')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force processing even if file has not changed')
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Path to config file')
            ->addOption('cache', null, InputOption::VALUE_OPTIONAL, 'Path to the cache file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->writeln($this->getApplication()->getName().' <info>'.$this->getApplication()->getVersion().'</info> by <comment>Realodix</comment>');
        $io->writeln('PHP runtime: <info>'.PHP_VERSION.'</info>');

        $io->newLine();

        // ---- Execute ----
        $startTime = microtime(true);
        $this->fixer->handle(
            $this->inputMode($input),
            $input->getOption('path'),
            $input->getOption('cache'),
            $input->getOption('config'),
        );
        $stats = $this->fixer->stats();

        if ($stats->allSkipped()) {
            $io->writeln('<info>All files have been processed.</info>');
            $io->newLine();
        } else {
            $io->writeln('');
            $io->writeln($stats);

            $io->writeln('------------------');
        }

        $executionTime = round(microtime(true) - $startTime, 2);
        $memoryUsage = memory_get_peak_usage(true);
        $memoryUsageFormatted = round($memoryUsage / 1024 / 1024, 2).' MB';
        $io->writeln("Time: {$executionTime} seconds, Memory: {$memoryUsageFormatted}");

        return Command::SUCCESS;
    }

    protected function inputMode(InputInterface $input): Mode
    {
        return match (true) {
            $input->getOption('force') => Mode::Force,
            default => Mode::Default,
        };
    }
}
