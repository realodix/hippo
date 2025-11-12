<?php

namespace Realodix\Hippo\Config;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

final class FixerConfig
{
    /** @var array<string> */
    public array $paths;

    /**
     * @param array<string, array<string>> $config
     * @param array{paths?: array<string>} $overrides
     */
    public function make(array $config, array $overrides): self
    {
        $this->paths = $this->paths(
            $overrides['paths'] ?? $config['paths'] ?? [],
            $config['ignores'] ?? [],
        );

        return $this;
    }

    /**
     * @param array<string> $paths
     * @param array<string> $ignores Excludes files by path
     * @return array<string>
     */
    private function paths(array $paths, array $ignores): array
    {
        $rootPath = base_path();
        $paths = !empty($paths) ? $paths : [$rootPath];

        $resolvedPaths = [];
        foreach ($paths as $path) {
            if (Path::isRelative($path)) {
                $path = Path::makeAbsolute($path, $rootPath);
            }

            if (is_dir($path)) {
                $finder = $this->finder($path, $ignores);
                foreach ($finder as $file) {
                    $resolvedPaths[] = $file->getRealPath();
                }
            } else {
                $resolvedPaths[] = $path;
            }
        }

        $resolvedPaths = array_map(fn($path) => Path::canonicalize($path), $resolvedPaths);

        return array_unique($resolvedPaths);
    }

    /**
     * @param string $dir The directory to use for the search
     * @param array<string> $ignores Excludes files by path
     * @return \Symfony\Component\Finder\Finder
     */
    public function finder(string $dir, array $ignores)
    {
        $ignores = array_map(
            fn($ignoredPaths) => Path::canonicalize($ignoredPaths),
            $ignores,
        );

        $finder = new Finder;
        $finder->files()
            ->in($dir)
            ->name(['*.txt', '*.adfl'])
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->notPath($ignores);

        return $finder;
    }
}
