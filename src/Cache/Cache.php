<?php

namespace Realodix\Hippo\Cache;

use Illuminate\Support\Arr;
use Realodix\Hippo\Config\Config;
use Realodix\Hippo\Enums\Mode;
use Realodix\Hippo\Enums\Scope;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class Cache
{
    const HASH_ALGO = 'xxh3';

    private bool $cacheCleared = false;

    public function __construct(
        private Repository $repository,
        private Filesystem $filesystem,
    ) {}

    public function repository(): Repository
    {
        return $this->repository;
    }

    /**
     * Prepare cache state before a run.
     * - If not forced: clean stale entries (files that no longer exist)
     * - If forced: clear entire cache, but only once per run
     *
     * @param \Realodix\Hippo\Config\Config $config The config to use
     * @param \Realodix\Hippo\Enums\Mode $mode The mode to use
     * @param \Realodix\Hippo\Enums\Scope $scope The scope to use (default: Scope::F)
     */
    public function prepareForRun(Config $config, Mode $mode, Scope $scope = Scope::F): void
    {
        $resolvedCachePath = $this->resolveCachePath($config->cacheDir);
        $this->repository()
            ->setCacheFile($resolvedCachePath)
            ->setScope($scope)
            ->load();

        if ($mode !== Mode::Force) {
            if ($scope === Scope::B) {
                // A list of currently configured output file paths from the Builder config
                $configuredOutput = array_map(
                    fn($filterSet) => $filterSet->outputPath,
                    $config->builder()->filterSet,
                );

                $this->cleanStaleEntries($configuredOutput);
            } else {
                $this->cleanStaleEntries();
            }
        }

        if ($mode == Mode::Force && !$this->cacheCleared) {
            $this->repository()->clear();
            $this->cacheCleared = true;
        }
    }

    /**
     * Set the cached data for the given key.
     *
     * @param string $key The key to set
     * @param string $value The reference value
     * @param bool $needHash Whether the value needs to be hashed
     */
    public function set(string $key, string $value, bool $needHash = false): void
    {
        $value = $needHash ? $this->hash($value) : $value;

        $this->repository()->set($key, [
            'reference' => $value,
        ]);
    }

    /**
     * Checks if a file has changed.
     *
     * @param string $key The cache key
     * @param string $value The reference value
     */
    public function isValid(string $key, string $value): bool
    {
        $cacheEntry = $this->repository()->get($key);

        return Arr::get($cacheEntry, 'reference') === $value;
    }

    /**
     * Remove cache entries that are no longer valid.
     *
     * - If a list of active files is provided, remove any entry not in that list.
     * - Otherwise, remove entries for files that no longer exist on disk.
     *
     * @param list<string> $paths A list of currently configured output file paths from the Builder config.
     */
    private function cleanStaleEntries(array $paths = []): void
    {
        $modified = false;

        foreach ($this->repository()->all() as $relativePath => $entry) {
            $isStale = !empty($paths)
                ? !in_array($relativePath, $paths)
                : !file_exists($relativePath);

            if ($isStale) {
                $this->repository()->remove($relativePath);
                $modified = true;
            }
        }

        if ($modified) {
            $this->repository()->save();
        }
    }

    /**
     * Resolves the cache file path and ensures its directory exists.
     *
     * @param string|null $cachePath The user-provided cache directory path (can be
     *                               relative, absolute, or null).
     * @return string The absolute path to the final cache file.
     */
    private function resolveCachePath(?string $cachePath): string
    {
        $fs = $this->filesystem;

        // 1. Default: no path provided -> use default cache file in baseDir
        if (empty($cachePath)) {
            return Repository::DEFAULT_CACHE_FILENAME;
        }

        $resolvedPath = Path::canonicalize($cachePath);

        // 2. If exists, determine type
        if ($fs->exists($resolvedPath)) {
            return is_dir($resolvedPath)
                ? Path::join($resolvedPath, Repository::DEFAULT_CACHE_FILENAME)
                : $resolvedPath;
        }

        // 3. Determine extension, but treat single-dot names like ".env" or ".tmp" as NO extension
        $basename = basename($resolvedPath);
        $rawExt = pathinfo($basename, PATHINFO_EXTENSION);
        // If basename starts with a dot AND contains no other dot, treat it as a dot-directory name (no extension).
        // Example: ".env" or ".tmp" -> consider NO extension.
        $isSingleDotName = str_starts_with($basename, '.') && strpos(substr($basename, 1), '.') === false;
        // hasExtension is true only when pathinfo reports an extension AND it's not a single-dot name
        $hasExtension = ($rawExt !== '') && !$isSingleDotName;

        // 4. Choose behavior
        if ($hasExtension) {
            $fs->mkdir(dirname($resolvedPath));

            return $resolvedPath;
        }

        // 5. Otherwise treat as directory (covers .tmp, .cache, .env and regular names)
        $fs->mkdir($resolvedPath);

        return Path::join($resolvedPath, Repository::DEFAULT_CACHE_FILENAME);
    }

    /**
     * @param string $data
     * @return string
     */
    public function hash($data)
    {
        return hash(self::HASH_ALGO, $data);
    }
}
