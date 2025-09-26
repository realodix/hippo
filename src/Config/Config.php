<?php

namespace Realodix\Hippo\Config;

use Nette\Schema\Processor;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

final class Config
{
    const FILENAME = 'hippo.yml';

    public ?string $cacheDir;

    /** @var array<string, mixed> */
    private array $configData = [];

    public function __construct(
        private BuilderConfig $builder,
        private FixerConfig $fixer,
        private Processor $schemaProcessor,
        private OutputInterface $output,
    ) {}

    /**
     * @param string|null $configFile Optional path to the configuration file
     * @param array<string, string|null> $overrides Optional configuration overrides
     */
    public function loadFromFile(?string $configFile, array $overrides = []): self
    {
        $configPath = $this->configPath($configFile);
        $configData = Yaml::parseFile($configPath);

        try {
            $this->schemaProcessor->process(Schema::define(), $configData);
            $this->configData = $configData;
        } catch (\Nette\Schema\ValidationException $e) {
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
        if (!isset($this->configData['builder'])) {
            throw new InvalidConfigurationException('The "builder" configuration is missing.');
        }

        return $this->builder->make($this->configData['builder']);
    }

    /**
     * @param array{paths?: array<int, string>} $overrides
     */
    public function fixer(array $overrides): FixerConfig
    {
        return $this->fixer->make($this->configData['fixer'] ?? [], $overrides);
    }

    private function configPath(?string $configFile): string
    {
        return base_path($configFile ?? self::FILENAME);
    }
}
