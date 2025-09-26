<?php

namespace Realodix\Hippo\Console;

use Realodix\Hippo\Compiler\Compiler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'compile',
    description: 'Combine multiple filters into one',
)]
class CompileCommand extends Command
{
    public function __construct(
        private Compiler $compiler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'File or directory to process')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force re-compile all sources');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->writeln($this->getApplication()->getName().' <info>'.$this->getApplication()->getVersion().'</info> by <comment>Realodix</comment>');
        $io->writeln('PHP Runtime: '.PHP_VERSION);
        $io->newLine();

        $start = microtime(true);
        $io->writeln('Combining filter list fragments into a filter list...');

        $this->compiler->handle(
            $input->getOption('config'),
            (bool) $input->getOption('force'),
        );

        $end = microtime(true);
        $io->newLine();
        $io->info('Done!');
        $io->newLine();
        $io->writeln('Time: '.round($end - $start, 3).' seconds');

        return Command::SUCCESS;
    }
}
