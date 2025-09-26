<?php

namespace Realodix\Hippo\Config;

use Symfony\Component\Filesystem\Path;

final class FixerConfig
{
    public string $path;

    /** @var list<string> */
    public array $ignore;

    /**
     * @param array<string, string|list<string>> $config
     * @param array<string> $overrides
     */
    public function make(array $config, array $overrides, string $workingDirectory): self
    {
        $this->path = $this->path($config, $overrides, $workingDirectory);

        $this->ignore = $this->ignore($config);

        return $this;
    }

    /**
     * @param array{path?: string} $config
     * @param array{path?: string} $overrides
     */
    private function path(array $config, array $overrides, string $workingDirectory): string
    {
        $path = $overrides['path'] ?? $config['path'] ?? $workingDirectory;

        if ($path === './') {
            return $workingDirectory;
        }

        return Path::canonicalize($path);
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
