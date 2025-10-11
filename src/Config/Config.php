<?php

namespace Realodix\Hippo\Config;

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
    ) {}

    /**
     * @param array<string> $overrides
     */
    public function loadFromFile(?string $configFile = null, array $overrides = []): self
    {
        $configPath = $this->configPath($configFile);
        $this->configData = file_exists($configPath)
            ? Yaml::parseFile($configPath)
            : [];

        $this->cacheDir = $overrides['cache_dir'] ?? $this->configData['cache_dir'] ?? null;

        return $this;
    }

    public function builder(): BuilderConfig
    {
        return $this->builder->make($this->configData['builder'], $this->workingDirectory());
    }

    /**
     * @param array<string> $overrides
     */
    public function fixer(array $overrides): FixerConfig
    {
        return $this->fixer->make($this->configData['fixer'] ?? [], $overrides, $this->workingDirectory());
    }

    private function configPath(?string $configFile): string
    {
        if (is_null($configFile)) {
            return $this->workingDirectory().DIRECTORY_SEPARATOR.self::FILENAME;
        }

        return $this->workingDirectory().DIRECTORY_SEPARATOR.$configFile;
    }

    /**
     * Get current working directory path.
     *
     * @throws \RuntimeException
     */
    private function workingDirectory(): string
    {
        $path = getcwd();

        if ($path === false) {
            throw new \RuntimeException('Unable to get current working directory.');
        }

        return $path;
    }
}
