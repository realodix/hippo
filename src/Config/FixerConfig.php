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
     * @param array<string, string|list<string>> $config
     * @param array{paths?: list<string>} $overrides
     */
    public function make(array $config, array $overrides, string $workingDirectory): self
    {
        $this->paths = $this->paths($config, $overrides, $workingDirectory);

        $this->ignore = $this->ignore($config);

        return $this;
    }

    /**
     * @param array{path?: string, paths?: list<string>} $config
     * @param array{paths?: list<string>} $overrides
     * @return list<string>
     */
    private function paths(array $config, array $overrides, string $workingDirectory): array
    {
        $paths = $overrides['paths'] ?? $config['paths'] ?? [$workingDirectory];

        $paths = array_map(function ($path) use ($workingDirectory) {
            if ($path === './') {
                return $workingDirectory;
            }

            if (Path::isRelative($path)) {
                $path = Path::makeAbsolute($path, $workingDirectory);
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
