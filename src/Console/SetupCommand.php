<?php

namespace Realodix\Hippo\Console;

use Realodix\Hippo\Config\Config;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'setup',
    description: 'Create a new hippo.yml file',
)]
class SetupCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filename = Config::FILENAME;

        $configFile = base_path($filename);

        if (file_exists($configFile)) {
            if (!$io->confirm("The {$filename} file already exists. Do you want to overwrite it?", false)) {
                $io->writeln('Aborted.');

                return Command::FAILURE;
            }
        }

        $this->createConfigFile($configFile);

        $io->success("{$filename} created successfully.");

        return Command::SUCCESS;
    }

    private function createConfigFile(string $configFile): void
    {
        $content = <<<'YML'
cache_dir: .tmp

fixer:
  # paths:
  #   - ./
  ignores:
    - file.txt
    - some/path/to/file.txt
    - path/to/source

builder:
  # output_dir: dist
  filter_list:
    # First filter list
    - filename: general_blocklist.txt
      # remove_duplicates: true
      metadata:
        # header: Adblock Plus 2.0
        title: General Blocklist
        # date_modified: false
        # version: true
        # custom: |
        #   Description: Filter list that specifically removes adverts.
        #   Expires: 6 days (update frequency)
        #   Homepage: https://example.org/
        #   License: MIT
      source:
        - blocklists/general/local-rules.txt
        - https://cdn.example.org/blocklists/general.txt
YML;

        file_put_contents($configFile, $content);
    }
}
