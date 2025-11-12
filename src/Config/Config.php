<?php

namespace Realodix\Hippo\Config;

use Illuminate\Support\Arr;
use Nette\Schema\Processor;
use Realodix\Hippo\Enums\Scope;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

final class Config
{
    const DEFAULT_FILENAME = 'hippo.yml';

    public ?string $cacheDir;

    /** @var array<string, mixed> */
    private array $config;

    public function __construct(
        private BuilderConfig $builder,
        private FixerConfig $fixer,
        private Processor $schemaProcessor,
        private OutputInterface $output,
    ) {}

    /**
     * @param string|null $path Optional path to the configuration file
     * @param array<string, string|null> $overrides Optional configuration overrides
     */
    public function load(?string $path, Scope $scope, array $overrides = []): self
    {
        $config = Yaml::parseFile($this->resolvePath($path));

        $this->validate($config, $scope);

        $this->config = $config;
        $this->cacheDir = $overrides['cache_dir'] ?? $config['cache_dir'] ?? null;

        return $this;
    }

    public function builder(): BuilderConfig
    {
        if (!isset($this->config['builder'])) {
            throw new InvalidConfigurationException('The "builder" configuration is missing.');
        }

        return $this->builder->make($this->config['builder']);
    }

    /**
     * @param array{paths?: array<string>} $overrides
     */
    public function fixer(array $overrides): FixerConfig
    {
        return $this->fixer->make($this->config['fixer'] ?? [], $overrides);
    }

    /**
     * Returns the absolute path to a configuration file.
     *
     * If no configuration file is specified, it defaults to the path of the
     * `hippo.yml` file.
     *
     * @param string|null $path Optional path to the configuration file
     */
    private function resolvePath(?string $path): string
    {
        return base_path($path ?? self::DEFAULT_FILENAME);
    }

    /**
     * @param array<string, mixed> $config
     * @param \Realodix\Hippo\Enums\Scope $scope
     */
    private function validate($config, $scope): void
    {
        if ($scope === Scope::B) {
            $config = Arr::only($config, ['cache_dir', 'builder']);
            $schema = Schema::builder();
        } else {
            $config = Arr::only($config, ['cache_dir', 'fixer']);
            $schema = Schema::fixer();
        }

        try {
            $this->schemaProcessor->process($schema, $config);
        } catch (\Nette\Schema\ValidationException $e) {
            $this->output->writeln('');
            $this->output->writeln('<error>Configuration error:</error>');

            foreach ($e->getMessages() as $message) {
                $this->output->writeln('- '.$message);
            }

            exit(1);
        }
    }
}
