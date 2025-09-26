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
     * @param string $cwd Current working directory
     */
    public function make(array $config, array $overrides, string $cwd): self
    {
        $this->paths = $this->paths($config, $overrides, $cwd);

        $this->ignore = $this->ignore($config);

        return $this;
    }

    /**
     * @param array{paths?: list<string>} $config
     * @param array{paths?: list<string>} $overrides
     * @param string $cwd Current working directory
     * @return list<string>
     */
    private function paths(array $config, array $overrides, string $cwd): array
    {
        $paths = $overrides['paths'] ?? $config['paths'] ?? [$cwd];

        $paths = array_map(function ($path) use ($cwd) {
            if ($path === './') {
                return $cwd;
            }

            if (Path::isRelative($path)) {
                $path = Path::makeAbsolute($path, $cwd);
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
