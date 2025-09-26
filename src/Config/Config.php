<?php

namespace Realodix\Hippo\Config;

use Nette\Schema\Processor;
use Nette\Schema\ValidationException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

final class Config
{
    const FILENAME = 'hippo.yml';

    public ?string $cacheDir = null;

    /** @var array<string, mixed> */
    private array $configData = [];

    public function __construct(
        private BuilderConfig $builder,
        private FixerConfig $fixer,
        private Processor $schemaProcessor,
        private OutputInterface $output,
    ) {}

    /**
     * @param string|null $configFile Optional path to the configuration file.
     * @param array<string> $overrides Optional configuration overrides
     *
     * @throws \Nette\Schema\ValidationException
     */
    public function loadFromFile(?string $configFile, array $overrides = []): self
    {
        $configPath = $this->configPath($configFile);
        $configData = Yaml::parseFile($configPath);

        try {
            $this->schemaProcessor->process(Schema::define(), $configData);
            $this->configData = $configData;
        } catch (ValidationException $e) {
            $this->output->writeln('');
            $this->output->writeln('<error>Configuration error:</error>');
            foreach ($e->getMessages() as $message) {
                $this->output->writeln('- '.$message);
            }
            exit(1);
        }

        $this->cacheDir = $overrides['cache_dir'] ?? $this->configData['cache_dir'] ?? null;

        return $this;
    }

    public function builder(): BuilderConfig
    {
        return $this->builder->make($this->configData['builder'], $this->cwd());
    }

    /**
     * @param array{paths?: list<string>} $overrides
     */
    public function fixer(array $overrides): FixerConfig
    {
        return $this->fixer->make($this->configData['fixer'] ?? [], $overrides, $this->cwd());
    }

    private function configPath(?string $configFile): string
    {
        return Path::join($this->cwd(), $configFile ?? self::FILENAME);
    }

    /**
     * Get current working directory path.
     *
     * @throws \RuntimeException
     */
    private function cwd(): string
    {
        $path = getcwd();

        if ($path === false) {
            throw new \RuntimeException('Unable to get current working directory.');
        }

        return $path;
    }
}
