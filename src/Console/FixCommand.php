<?php

namespace Realodix\Hippo\Console;

use Realodix\Hippo\Compiler\Config;
use Realodix\Hippo\Compiler\ValueObject\ProjectConfig;
use Realodix\Hippo\Enums\Mode;
use Realodix\Hippo\Processor\FileHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
    private ProjectConfig $projectConfig;

    public function __construct(
        private FileHandler $fileHandler,
        private Config $config,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('path', InputArgument::REQUIRED, 'File or directory to process')
            ->addOption('ignore', 'i',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Files or directories to ignore',
                ['requirements.txt', 'templates', 'node_modules', 'vendor'],
            )
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force processing even if file has not changed')
            ->addOption('partial', 'p', InputOption::VALUE_NONE, 'Use partial mode')
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'File or directory to process')
            ->addOption('cache', null, InputOption::VALUE_OPTIONAL, 'Path to the cache file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ($input->getOption('force') && $input->getOption('partial')) {
            throw new \InvalidArgumentException('The --force and --partial options cannot be used together.');
        }

        $io->writeln($this->getApplication()->getName().' <info>'.$this->getApplication()->getVersion().'</info> by <comment>Realodix</comment>');
        $io->writeln('PHP runtime: <info>'.PHP_VERSION.'</info>');

        $io->newLine();

        // ---- Execute ----
        $startTime = microtime(true);
        $stats = $this->fileHandler->handle(
            $input->getArgument('path'),
            $input->getOption('ignore'),
            $this->inputMode($input),
            $this->inputCache($input),
        );

        if (!empty($stats->total) && $stats->processed < 1) {
            $io->info('All files have already been processed.');
        } else {
            $io->writeln(sprintf(
                "\nTotal: %d, Processed: %d, Skipped: %d",
                $stats->total,
                $stats->processed,
                $stats->skipped,
            ));

            $io->writeln('------------------');
        }

        $executionTime = round(microtime(true) - $startTime, 2);
        $memoryUsage = memory_get_peak_usage(true);
        $memoryUsageFormatted = round($memoryUsage / 1024 / 1024, 2).' MB';
        $io->writeln("Time: {$executionTime} seconds, Memory: {$memoryUsageFormatted}");

        return Command::SUCCESS;
    }

    protected function inputCache(InputInterface $input): string
    {
        if (is_null($input->getOption('cache'))) {
            $this->projectConfig = $this->config
                ->createFromFile($input->getOption('config'));

            return $this->projectConfig->cacheDir;
        }

        return $input->getOption('cache');
    }

    protected function inputMode(InputInterface $input): Mode
    {
        return match (true) {
            $input->getOption('force') => Mode::Force,
            $input->getOption('partial') => Mode::Partial,
            default => Mode::Default,
        };
    }
}
