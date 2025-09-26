<?php

namespace Realodix\Hippo\Compiler;

use Realodix\Hippo\Compiler\ValueObject\FilterConfig;
use Realodix\Hippo\Compiler\ValueObject\ProjectConfig;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

final class Config
{
    public function __construct(
        private Filesystem $filesystem,
    ) {}

    public function createFromFile(?string $configFile): ProjectConfig
    {
        $configData = Yaml::parseFile($this->configPath($configFile));
        $compilerConfig = $configData['compiler'];
        $filters = [];

        foreach ($compilerConfig['filter_list'] as $list) {
            $filters[] = new FilterConfig(
                outputFile: $list['outputFile'] ?? pathinfo($configFile, PATHINFO_FILENAME).'.txt',
                metadata: $list['metadata'] ?? [],
                source: $list['source'] ?? [],
            );
        }

        return new ProjectConfig(
            cacheDir: $this->cacheDir($configData),
            outputDir: $this->outputDir($compilerConfig),
            filters: $filters,
        );
    }

    private function configPath(?string $configFile): string
    {
        if (is_null($configFile)) {
            return $this->workingDirectory().'/hippo.yml';
        }

        return $this->workingDirectory().DIRECTORY_SEPARATOR.$configFile;
    }

    /**
     * Returns the cache directory path from the config data, or null if not set.
     *
     * @param array{cacheDir?: string} $configData The config data to resolve the cache directory from
     * @return string|null The cache directory path, or null if not set
     */
    private function cacheDir(array $configData): ?string
    {
        return $configData['cacheDir'] ?? null;
    }

    /**
     * Resolves the output directory from the config data.
     *
     * @param array{output_dir?: string} $configData The config data to resolve the output
     *                                               directory from
     */
    private function outputDir(array $configData): string
    {
        if (isset($configData['output_dir']) && !empty($configData['output_dir'])) {
            $outputDir = $configData['output_dir'];

            if (substr($outputDir, 0, 1) !== DIRECTORY_SEPARATOR) {
                $outputDir = DIRECTORY_SEPARATOR.$outputDir;
            }

            $fullOutputDir = $this->workingDirectory().$outputDir;
            if (!$this->filesystem->exists($fullOutputDir)) {
                $this->filesystem->mkdir($fullOutputDir);
            }

            return $fullOutputDir;
        }

        return $this->workingDirectory();
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
