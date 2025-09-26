<?php

namespace Realodix\Hippo\Config;

use Symfony\Component\Filesystem\Path;

final class FixerConfig
{
    /** @var list<string> */
    public array $paths;

    /** @var list<string> */
    public array $ignore;

    /**
     * @param array<string, list<string>> $config
     * @param array{paths?: list<string>} $overrides
     */
    public function make(array $config, array $overrides): self
    {
        $this->paths = $this->paths($config, $overrides);

        $this->ignore = $this->ignore($config);

        return $this;
    }

    /**
     * @param array{paths?: list<string>} $config
     * @param array{paths?: list<string>} $overrides
     * @return list<string>
     */
    private function paths(array $config, array $overrides): array
    {
        $rootPath = base_path();
        $paths = $overrides['paths'] ?? $config['paths'] ?? [$rootPath];

        $paths = array_map(function ($path) use ($rootPath) {
            if ($path === './') {
                return $rootPath;
            }

            if (Path::isRelative($path)) {
                $path = Path::makeAbsolute($path, $rootPath);
            }

            return Path::canonicalize($path);
        }, $paths);

        return array_unique($paths); // Remove duplicate paths
    }

    /**
     * @param array<string, list<string>> $config
     * @return list<string>
     */
    private function ignore(array $config): array
    {
        return array_map(
            fn($ignorePath) => Path::canonicalize($ignorePath),
            $config['ignore'] ?? [],
        );
    }
}
